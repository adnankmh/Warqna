import 'dart:math';

/// Local, deterministic rule engines for board games that cannot be represented
/// by the shared trick/rummy state machine.
///
/// The online Laravel engines remain authoritative for multiplayer rooms. These
/// engines make the same product games fully playable against bots when the API
/// is unavailable.
abstract class OfflineSpecialEngine {
  int get playerCount;
  Map<String, dynamic> publicState();
  void action(String type, Map<String, dynamic> payload);
  void timeout();
}

OfflineSpecialEngine? createOfflineSpecialEngine({
  required String gameId,
  required int requestedPlayerCount,
  required Random random,
}) {
  return switch (gameId) {
    'domino' => _OfflineDomino(
        requestedPlayerCount: requestedPlayerCount,
        random: random,
      ),
    'backgammon' => _OfflineBackgammon(random: random),
    'solitaire_multiplayer' => _OfflineSolitaire(
        requestedPlayerCount: requestedPlayerCount,
        random: random,
      ),
    'jackaroo' => _OfflineJackaroo(random: random),
    'leekha' => _OfflineLeekha(random: random),
    _ => null,
  };
}

String _playerKey(int seat) => seat == 0 ? 'user:0' : 'bot:$seat';

class _OfflineDomino implements OfflineSpecialEngine {
  _OfflineDomino({
    required int requestedPlayerCount,
    required this.random,
  }) : _count = requestedPlayerCount >= 4 ? 4 : 2 {
    _startRound();
    _autoBots();
  }

  final Random random;
  final int _count;
  final List<List<String>> hands = <List<String>>[];
  final List<String> boneyard = <String>[];
  final List<Map<String, dynamic>> board = <Map<String, dynamic>>[];
  final List<int> scores = <int>[];
  final List<String> messages = <String>[];
  int currentSeat = 0;
  int? left;
  int? right;
  int passesInRow = 0;
  int round = 0;
  bool gameOver = false;
  int? winnerSeat;

  @override
  int get playerCount => _count;

  void _startRound() {
    round++;
    final tiles = <String>[
      for (var a = 0; a <= 6; a++)
        for (var b = a; b <= 6; b++) '$a-$b',
    ]..shuffle(random);
    hands
      ..clear()
      ..addAll(List<List<String>>.generate(_count, (_) => <String>[]));
    if (scores.isEmpty) scores.addAll(List<int>.filled(_count, 0));
    for (var seat = 0; seat < _count; seat++) {
      hands[seat].addAll(tiles.take(7));
      tiles.removeRange(0, 7);
      hands[seat].sort(_tileSort);
    }
    boneyard
      ..clear()
      ..addAll(tiles);
    board.clear();
    left = null;
    right = null;
    passesInRow = 0;
    currentSeat = _openingSeat();
    messages.add('الجولة $round: يبدأ ${_playerKey(currentSeat)} بأقوى دبل.');
  }

  int _openingSeat() {
    for (var value = 6; value >= 0; value--) {
      final tile = '$value-$value';
      for (var seat = 0; seat < _count; seat++) {
        if (hands[seat].contains(tile)) return seat;
      }
    }
    var bestSeat = 0;
    var bestPips = -1;
    for (var seat = 0; seat < _count; seat++) {
      for (final tile in hands[seat]) {
        final pips = _parts(tile).fold<int>(0, (sum, value) => sum + value);
        if (pips > bestPips) {
          bestPips = pips;
          bestSeat = seat;
        }
      }
    }
    return bestSeat;
  }

  int _tileSort(String a, String b) {
    final pa = _parts(a);
    final pb = _parts(b);
    final da = pa[0] == pa[1] ? 100 : 0;
    final db = pb[0] == pb[1] ? 100 : 0;
    return (db + pb[0] + pb[1]).compareTo(da + pa[0] + pa[1]);
  }

  List<int> _parts(String tile) {
    final parts = tile.split('-');
    return <int>[
      int.tryParse(parts.isEmpty ? '' : parts.first) ?? 0,
      int.tryParse(parts.length > 1 ? parts[1] : '') ?? 0,
    ];
  }

  List<Map<String, dynamic>> _actionsFor(int seat) {
    if (gameOver || seat != currentSeat) return <Map<String, dynamic>>[];
    final actions = <Map<String, dynamic>>[];
    for (final tile in hands[seat]) {
      final parts = _parts(tile);
      if (board.isEmpty) {
        actions.add(<String, dynamic>{
          'type': 'play_tile',
          'tile': tile,
          'side': 'right',
        });
      } else {
        if (parts.contains(left)) {
          actions.add(<String, dynamic>{
            'type': 'play_tile',
            'tile': tile,
            'side': 'left',
          });
        }
        if (parts.contains(right)) {
          actions.add(<String, dynamic>{
            'type': 'play_tile',
            'tile': tile,
            'side': 'right',
          });
        }
      }
    }
    if (actions.isNotEmpty) return actions;
    if (_count == 2 && boneyard.isNotEmpty) {
      return <Map<String, dynamic>>[
        <String, dynamic>{'type': 'draw'},
      ];
    }
    return <Map<String, dynamic>>[
      <String, dynamic>{'type': 'pass'},
    ];
  }

  @override
  void action(String type, Map<String, dynamic> payload) {
    if (currentSeat != 0) _autoBots();
    _apply(0, type, payload);
    _autoBots();
  }

  void _apply(int seat, String type, Map<String, dynamic> payload) {
    if (gameOver || seat != currentSeat) {
      throw StateError('ليست دورك.');
    }
    final actions = _actionsFor(seat);
    if (type == 'draw') {
      if (!actions.any((item) => item['type'] == 'draw')) {
        throw StateError('السحب غير متاح مع وجود حجر صالح.');
      }
      hands[seat].add(boneyard.removeLast());
      hands[seat].sort(_tileSort);
      messages.add('${_playerKey(seat)} سحب حجراً.');
      if (_actionsFor(seat).every((item) => item['type'] != 'play_tile') &&
          boneyard.isEmpty) {
        _finishTurn(seat);
      }
      return;
    }
    if (type == 'pass') {
      if (!actions.any((item) => item['type'] == 'pass')) {
        throw StateError('لا يمكن التمرير مع وجود حركة قانونية.');
      }
      passesInRow++;
      messages.add('${_playerKey(seat)} مرّر لعدم وجود حجر مطابق.');
      if (passesInRow >= _count) {
        _finishRound(_lowestPipsSeat());
      } else {
        _finishTurn(seat);
      }
      return;
    }
    if (type != 'play_tile') throw StateError('حركة دومينو غير معروفة.');
    final tile = payload['tile']?.toString() ?? '';
    final side = payload['side']?.toString() ?? 'right';
    final candidate = actions.cast<Map<String, dynamic>?>().firstWhere(
          (item) => item?['type'] == 'play_tile' &&
              item?['tile'] == tile &&
              item?['side'] == side,
          orElse: () => null,
        );
    if (candidate == null || !hands[seat].remove(tile)) {
      throw StateError('الحجر لا يطابق الطرف المفتوح.');
    }
    final parts = _parts(tile);
    if (board.isEmpty) {
      left = parts[0];
      right = parts[1];
      board.add(<String, dynamic>{
        'tile': tile,
        'left': left,
        'right': right,
      });
    } else if (side == 'left') {
      final outer = parts[0] == left ? parts[1] : parts[0];
      board.insert(0, <String, dynamic>{
        'tile': tile,
        'left': outer,
        'right': left,
      });
      left = outer;
    } else {
      final outer = parts[0] == right ? parts[1] : parts[0];
      board.add(<String, dynamic>{
        'tile': tile,
        'left': right,
        'right': outer,
      });
      right = outer;
    }
    passesInRow = 0;
    messages.add('${_playerKey(seat)} لعب $tile.');
    if (hands[seat].isEmpty) {
      _finishRound(seat);
    } else {
      _finishTurn(seat);
    }
  }

  void _finishTurn(int seat) {
    currentSeat = (seat + 1) % _count;
  }

  int _pipSum(List<String> hand) => hand.fold<int>(
        0,
        (sum, tile) => sum + _parts(tile).fold<int>(0, (a, b) => a + b),
      );

  int _lowestPipsSeat() {
    var winner = 0;
    var lowest = 999;
    for (var seat = 0; seat < _count; seat++) {
      final pips = _pipSum(hands[seat]);
      if (pips < lowest) {
        lowest = pips;
        winner = seat;
      }
    }
    return winner;
  }

  void _finishRound(int winner) {
    var award = 0;
    for (var seat = 0; seat < _count; seat++) {
      if (seat != winner) award += _pipSum(hands[seat]);
    }
    scores[winner] += award;
    messages.add('${_playerKey(winner)} فاز بالجولة وحصل على $award نقطة.');
    if (scores[winner] >= 100) {
      gameOver = true;
      winnerSeat = winner;
      messages.add('انتهت المباراة عند 100 نقطة.');
    } else {
      _startRound();
    }
  }

  void _autoBots() {
    var guard = 0;
    while (!gameOver && currentSeat != 0 && guard++ < 160) {
      final actions = _actionsFor(currentSeat);
      final playable = actions
          .where((item) => item['type'] == 'play_tile')
          .toList()
        ..sort((a, b) {
          final ap = _parts(a['tile'].toString()).fold<int>(0, (x, y) => x + y);
          final bp = _parts(b['tile'].toString()).fold<int>(0, (x, y) => x + y);
          return bp.compareTo(ap);
        });
      final chosen = playable.isNotEmpty ? playable.first : actions.first;
      final type = chosen['type'].toString();
      _apply(currentSeat, type, Map<String, dynamic>.from(chosen)..remove('type'));
    }
  }

  @override
  void timeout() {
    if (currentSeat != 0) {
      _autoBots();
      return;
    }
    final actions = _actionsFor(0);
    if (actions.isEmpty) return;
    final chosen = actions.first;
    _apply(0, chosen['type'].toString(),
        Map<String, dynamic>.from(chosen)..remove('type'));
    _autoBots();
  }

  @override
  Map<String, dynamic> publicState() => <String, dynamic>{
        'phase': gameOver ? 'finished' : 'playing',
        'engine_phase': gameOver ? 'finished' : 'playing',
        'hand': List<String>.from(hands[0]),
        'legal_cards': _actionsFor(0)
            .where((item) => item['type'] == 'play_tile')
            .map((item) => item['tile'].toString())
            .toSet()
            .toList(),
        'available_actions': _actionsFor(0),
        'current_player': _playerKey(currentSeat),
        'board': board.map((item) => Map<String, dynamic>.from(item)).toList(),
        'table': board.map((item) => item['tile'].toString()).toList(),
        'left': left,
        'right': right,
        'boneyard_count': boneyard.length,
        'scores': <String, int>{
          for (var seat = 0; seat < _count; seat)
            _playerKey(seat): scores[seat],
        },
        'round': round,
        'messages': messages.takeLast(35),
        'game_over': gameOver,
        'winner': winnerSeat == null ? null : _playerKey(winnerSeat!),
        'offline_rules': 'double_six_to_100_v187',
      };
}

class _BgPoint {
  _BgPoint(this.owner, this.count);
  int? owner;
  int count;

  _BgPoint copy() => _BgPoint(owner, count);
}

class _BgMove {
  const _BgMove({
    required this.from,
    required this.to,
    required this.die,
    required this.hit,
    required this.bearOff,
  });
  final int from;
  final int to;
  final int die;
  final bool hit;
  final bool bearOff;

  Map<String, dynamic> toAction() => <String, dynamic>{
        'type': 'move',
        'from': from,
        'to': to,
        'die': die,
        'hit': hit,
        'bear_off': bearOff,
      };
}

class _OfflineBackgammon implements OfflineSpecialEngine {
  _OfflineBackgammon({required this.random}) {
    _reset();
  }

  final Random random;
  final Map<int, _BgPoint> points = <int, _BgPoint>{};
  final List<int> dice = <int>[];
  final List<int> movesLeft = <int>[];
  final List<int> bar = <int>[0, 0];
  final List<int> borneOff = <int>[0, 0];
  final List<int> score = <int>[0, 0];
  final List<String> messages = <String>[];
  int currentSeat = 0;
  bool gameOver = false;
  int? winnerSeat;
  int winMultiplier = 1;

  @override
  int get playerCount => 2;

  void _reset() {
    points.clear();
    for (var point = 1; point <= 24; point++) {
      points[point] = _BgPoint(null, 0);
    }
    points[1] = _BgPoint(0, 2);
    points[12] = _BgPoint(0, 5);
    points[17] = _BgPoint(0, 3);
    points[19] = _BgPoint(0, 5);
    points[24] = _BgPoint(1, 2);
    points[13] = _BgPoint(1, 5);
    points[8] = _BgPoint(1, 3);
    points[6] = _BgPoint(1, 5);
    dice.clear();
    movesLeft.clear();
    bar
      ..[0] = 0
      ..[1] = 0;
    borneOff
      ..[0] = 0
      ..[1] = 0;
    currentSeat = 0;
    gameOver = false;
    winnerSeat = null;
    winMultiplier = 1;
    messages.add('طاولة كاملة: البار أولاً، أكبر عدد من أحجار النرد، ثم الإخراج.');
  }

  int _direction(int seat) => seat == 0 ? 1 : -1;

  bool _allInHome(int seat) {
    if (bar[seat] > 0) return false;
    for (final entry in points.entries) {
      if (entry.value.owner != seat || entry.value.count < 1) continue;
      if (seat == 0 && (entry.key < 19 || entry.key > 24)) return false;
      if (seat == 1 && (entry.key < 1 || entry.key > 6)) return false;
    }
    return true;
  }

  bool _canBearOff(int seat, int from, int die) {
    if (!_allInHome(seat)) return false;
    final distance = seat == 0 ? 25 - from : from;
    if (die == distance) return true;
    if (die < distance) return false;
    if (seat == 0) {
      for (var point = 19; point < from; point++) {
        if (points[point]!.owner == seat && points[point]!.count > 0) {
          return false;
        }
      }
    } else {
      for (var point = 6; point > from; point--) {
        if (points[point]!.owner == seat && points[point]!.count > 0) {
          return false;
        }
      }
    }
    return true;
  }

  List<_BgMove> _rawMoves(
    int seat, {
    Map<int, _BgPoint>? board,
    List<int>? barState,
    List<int>? offState,
    List<int>? remaining,
  }) {
    final boardValue = board ?? points;
    final barValue = barState ?? bar;
    final diceValue = remaining ?? movesLeft;
    final result = <_BgMove>[];
    final uniqueDice = diceValue.toSet();
    if (uniqueDice.isEmpty) return result;
    final direction = _direction(seat);
    for (final die in uniqueDice) {
      if (barValue[seat] > 0) {
        final from = direction == 1 ? 0 : 25;
        final to = direction == 1 ? die : 25 - die;
        final destination = boardValue[to]!;
        final open = destination.owner == null ||
            destination.owner == seat ||
            destination.count <= 1;
        if (open) {
          result.add(_BgMove(
            from: from,
            to: to,
            die: die,
            hit: destination.owner != null &&
                destination.owner != seat &&
                destination.count == 1,
            bearOff: false,
          ));
        }
        continue;
      }
      for (final entry in boardValue.entries) {
        if (entry.value.owner != seat || entry.value.count < 1) continue;
        final to = entry.key + direction * die;
        if (to >= 1 && to <= 24) {
          final destination = boardValue[to]!;
          final open = destination.owner == null ||
              destination.owner == seat ||
              destination.count <= 1;
          if (open) {
            result.add(_BgMove(
              from: entry.key,
              to: to,
              die: die,
              hit: destination.owner != null &&
                  destination.owner != seat &&
                  destination.count == 1,
              bearOff: false,
            ));
          }
        } else if (board == null && _canBearOff(seat, entry.key, die)) {
          result.add(_BgMove(
            from: entry.key,
            to: direction == 1 ? 25 : 0,
            die: die,
            hit: false,
            bearOff: true,
          ));
        } else if (board != null &&
            _canBearOffSnapshot(seat, entry.key, die, boardValue, barValue)) {
          result.add(_BgMove(
            from: entry.key,
            to: direction == 1 ? 25 : 0,
            die: die,
            hit: false,
            bearOff: true,
          ));
        }
      }
    }
    return result;
  }

  bool _canBearOffSnapshot(
    int seat,
    int from,
    int die,
    Map<int, _BgPoint> board,
    List<int> barState,
  ) {
    if (barState[seat] > 0) return false;
    for (final entry in board.entries) {
      if (entry.value.owner != seat || entry.value.count < 1) continue;
      if (seat == 0 && entry.key < 19) return false;
      if (seat == 1 && entry.key > 6) return false;
    }
    final distance = seat == 0 ? 25 - from : from;
    if (die == distance) return true;
    if (die < distance) return false;
    if (seat == 0) {
      for (var point = 19; point < from; point++) {
        if (board[point]!.owner == seat && board[point]!.count > 0) return false;
      }
    } else {
      for (var point = 6; point > from; point--) {
        if (board[point]!.owner == seat && board[point]!.count > 0) return false;
      }
    }
    return true;
  }

  ({
    Map<int, _BgPoint> board,
    List<int> bar,
    List<int> off,
    List<int> remaining
  }) _simulate(
    int seat,
    _BgMove move,
    Map<int, _BgPoint> source,
    List<int> barState,
    List<int> offState,
    List<int> remaining,
  ) {
    final nextBoard = <int, _BgPoint>{
      for (final entry in source.entries) entry.key: entry.value.copy(),
    };
    final nextBar = List<int>.from(barState);
    final nextOff = List<int>.from(offState);
    final nextRemaining = List<int>.from(remaining);
    if ((seat == 0 && move.from == 0) || (seat == 1 && move.from == 25)) {
      nextBar[seat]--;
    } else {
      final sourcePoint = nextBoard[move.from]!;
      sourcePoint.count--;
      if (sourcePoint.count <= 0) sourcePoint.owner = null;
    }
    if (move.bearOff) {
      nextOff[seat]++;
    } else {
      final destination = nextBoard[move.to]!;
      if (destination.owner != null &&
          destination.owner != seat &&
          destination.count == 1) {
        nextBar[destination.owner!]++;
        destination
          ..owner = seat
          ..count = 1;
      } else {
        if (destination.owner != seat) {
          destination
            ..owner = seat
            ..count = 0;
        }
        destination.count++;
      }
    }
    nextRemaining.remove(move.die);
    return (
      board: nextBoard,
      bar: nextBar,
      off: nextOff,
      remaining: nextRemaining,
    );
  }

  int _maxPlayable(
    int seat,
    Map<int, _BgPoint> boardValue,
    List<int> barValue,
    List<int> offValue,
    List<int> remaining,
    int depth,
  ) {
    if (remaining.isEmpty || depth <= 0) return 0;
    final moves = _rawMoves(
      seat,
      board: boardValue,
      barState: barValue,
      offState: offValue,
      remaining: remaining,
    );
    var best = 0;
    for (final move in moves) {
      final next =
          _simulate(seat, move, boardValue, barValue, offValue, remaining);
      best = max(
        best,
        1 +
            _maxPlayable(
              seat,
              next.board,
              next.bar,
              next.off,
              next.remaining,
              depth - 1,
            ),
      );
    }
    return best;
  }

  List<_BgMove> _legalMoves(int seat) {
    final raw = _rawMoves(seat);
    if (raw.length <= 1) return raw;
    final ranked = <_BgMove, int>{};
    var best = 0;
    for (final move in raw) {
      final next = _simulate(seat, move, points, bar, borneOff, movesLeft);
      final count = 1 +
          _maxPlayable(
            seat,
            next.board,
            next.bar,
            next.off,
            next.remaining,
            3,
          );
      ranked[move] = count;
      best = max(best, count);
    }
    var legal = raw.where((move) => ranked[move] == best).toList();
    if (best == 1 && movesLeft.toSet().length > 1) {
      final largest = legal.map((move) => move.die).reduce(max);
      legal = legal.where((move) => move.die == largest).toList();
    }
    return legal;
  }

  List<Map<String, dynamic>> _actionsFor(int seat) {
    if (gameOver || currentSeat != seat) return <Map<String, dynamic>>[];
    if (movesLeft.isEmpty) {
      return <Map<String, dynamic>>[
        <String, dynamic>{'type': 'roll'},
      ];
    }
    final moves = _legalMoves(seat);
    if (moves.isEmpty) {
      return <Map<String, dynamic>>[
        <String, dynamic>{'type': 'pass'},
      ];
    }
    return moves.map((move) => move.toAction()).toList();
  }

  @override
  void action(String type, Map<String, dynamic> payload) {
    if (currentSeat != 0) _autoBots();
    _apply(0, type, payload);
    _autoBots();
  }

  void _apply(int seat, String type, Map<String, dynamic> payload) {
    if (gameOver || currentSeat != seat) throw StateError('ليست دورك.');
    if (type == 'roll') {
      if (movesLeft.isNotEmpty || dice.isNotEmpty) {
        throw StateError('استخدم رمية النرد الحالية أولاً.');
      }
      final first = random.nextInt(6) + 1;
      final second = random.nextInt(6) + 1;
      dice
        ..clear()
        ..addAll(<int>[first, second]);
      movesLeft
        ..clear()
        ..addAll(first == second
            ? <int>[first, first, first, first]
            : <int>[first, second]);
      messages.add('${_playerKey(seat)} رمى $first-$second.');
      if (_legalMoves(seat).isEmpty) _finishTurn();
      return;
    }
    if (type == 'pass') {
      if (movesLeft.isEmpty || _legalMoves(seat).isNotEmpty) {
        throw StateError('التمرير غير قانوني.');
      }
      _finishTurn();
      return;
    }
    if (type != 'move') throw StateError('حركة طاولة غير معروفة.');
    final from = int.tryParse(payload['from']?.toString() ?? '') ?? -1;
    final to = int.tryParse(payload['to']?.toString() ?? '') ?? -1;
    final move = _legalMoves(seat).cast<_BgMove?>().firstWhere(
          (candidate) => candidate?.from == from && candidate?.to == to,
          orElse: () => null,
        );
    if (move == null) {
      throw StateError('الحركة تخالف النرد أو أولوية البار أو قاعدة أكبر عدد.');
    }
    _execute(seat, move);
    movesLeft.remove(move.die);
    messages.add(
        '${_playerKey(seat)} حرّك ${move.from} ← ${move.to} بالنرد ${move.die}.');
    if (borneOff[seat] >= 15) {
      gameOver = true;
      winnerSeat = seat;
      final opponent = 1 - seat;
      winMultiplier = borneOff[opponent] > 0
          ? 1
          : (_isBackgammonLoss(seat, opponent) ? 3 : 2);
      score[seat] += winMultiplier;
      messages.add('فوز ${winMultiplier == 3 ? 'باكغمون' : winMultiplier == 2 ? 'غامون' : 'عادي'}.');
      return;
    }
    if (movesLeft.isEmpty || _legalMoves(seat).isEmpty) _finishTurn();
  }

  void _execute(int seat, _BgMove move) {
    if ((seat == 0 && move.from == 0) || (seat == 1 && move.from == 25)) {
      bar[seat]--;
    } else {
      final source = points[move.from]!;
      source.count--;
      if (source.count <= 0) source.owner = null;
    }
    if (move.bearOff) {
      borneOff[seat]++;
      return;
    }
    final destination = points[move.to]!;
    if (destination.owner != null &&
        destination.owner != seat &&
        destination.count == 1) {
      bar[destination.owner!]++;
      messages.add('${_playerKey(seat)} ضرب حجراً.');
      destination
        ..owner = seat
        ..count = 1;
    } else {
      if (destination.owner != seat) {
        destination
          ..owner = seat
          ..count = 0;
      }
      destination.count++;
    }
  }

  bool _isBackgammonLoss(int winner, int loser) {
    if (bar[loser] > 0) return true;
    final winnerHome = winner == 0 ? List<int>.generate(6, (i) => 19 + i) : List<int>.generate(6, (i) => 1 + i);
    return winnerHome.any(
      (point) => points[point]!.owner == loser && points[point]!.count > 0,
    );
  }

  void _finishTurn() {
    currentSeat = 1 - currentSeat;
    dice.clear();
    movesLeft.clear();
  }

  void _autoBots() {
    var guard = 0;
    while (!gameOver && currentSeat != 0 && guard++ < 32) {
      if (movesLeft.isEmpty) {
        _apply(currentSeat, 'roll', const <String, dynamic>{});
        continue;
      }
      final moves = _legalMoves(currentSeat);
      if (moves.isEmpty) {
        _apply(currentSeat, 'pass', const <String, dynamic>{});
        continue;
      }
      moves.sort((a, b) {
        final aScore = (a.hit ? 100 : 0) + (a.bearOff ? 70 : 0) + a.die;
        final bScore = (b.hit ? 100 : 0) + (b.bearOff ? 70 : 0) + b.die;
        return bScore.compareTo(aScore);
      });
      final chosen = moves.first;
      _apply(currentSeat, 'move', <String, dynamic>{
        'from': chosen.from,
        'to': chosen.to,
      });
    }
  }

  @override
  void timeout() {
    if (currentSeat != 0) {
      _autoBots();
      return;
    }
    final actions = _actionsFor(0);
    if (actions.isEmpty) return;
    final chosen = actions.first;
    _apply(0, chosen['type'].toString(),
        Map<String, dynamic>.from(chosen)..remove('type'));
    _autoBots();
  }

  @override
  Map<String, dynamic> publicState() => <String, dynamic>{
        'phase': gameOver ? 'finished' : 'playing',
        'engine_phase': gameOver ? 'finished' : 'playing',
        'hand': <String>[],
        'legal_cards': <String>[],
        'available_actions': _actionsFor(0),
        'current_player': _playerKey(currentSeat),
        'points': <String, Map<String, dynamic>>{
          for (var point = 1; point <= 24; point++)
            '$point': <String, dynamic>{
              'owner': points[point]!.owner == null
                  ? null
                  : _playerKey(points[point]!.owner!),
              'count': points[point]!.count,
            },
        },
        'dice': List<int>.from(dice),
        'moves_left': List<int>.from(movesLeft),
        'bar': <String, int>{'user:0': bar[0], 'bot:1': bar[1]},
        'borne_off': <String, int>{
          'user:0': borneOff[0],
          'bot:1': borneOff[1],
        },
        'scores': <String, int>{'user:0': score[0], 'bot:1': score[1]},
        'messages': messages.takeLast(35),
        'game_over': gameOver,
        'winner': winnerSeat == null ? null : _playerKey(winnerSeat!),
        'win_multiplier': winMultiplier,
        'offline_rules': 'backgammon_max_dice_v187',
      };
}

class _SolitaireColumn {
  _SolitaireColumn({List<String>? down, List<String>? up})
      : down = down ?? <String>[],
        up = up ?? <String>[];
  final List<String> down;
  final List<String> up;
}

class _SolitaireSeat {
  final List<_SolitaireColumn> tableau = <_SolitaireColumn>[];
  final List<String> stock = <String>[];
  final List<String> waste = <String>[];
  final Map<String, List<String>> foundation = <String, List<String>>{
    'C': <String>[],
    'D': <String>[],
    'S': <String>[],
    'H': <String>[],
  };
}

class _OfflineSolitaire implements OfflineSpecialEngine {
  _OfflineSolitaire({
    required int requestedPlayerCount,
    required this.random,
  }) : _count = requestedPlayerCount.clamp(2, 4).toInt() {
    _setup();
    _autoBots();
  }

  final int _count;
  final Random random;
  final List<_SolitaireSeat> seats = <_SolitaireSeat>[];
  final List<String> messages = <String>[];
  int currentSeat = 0;
  bool gameOver = false;
  int? winnerSeat;

  static const ranks = <String>[
    'A',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    'J',
    'Q',
    'K',
  ];
  static const suits = <String>['C', 'D', 'S', 'H'];

  @override
  int get playerCount => _count;

  void _setup() {
    seats.clear();
    for (var seatIndex = 0; seatIndex < _count; seatIndex++) {
      final seat = _SolitaireSeat();
      final deck = <String>[
        for (final suit in suits)
          for (final rank in ranks) '${rank}_$suit',
      ]..shuffle(random);
      for (var column = 0; column < 7; column++) {
        final down = <String>[];
        for (var card = 0; card < column; card++) {
          down.add(deck.removeLast());
        }
        seat.tableau.add(
          _SolitaireColumn(down: down, up: <String>[deck.removeLast()]),
        );
      }
      seat.stock.addAll(deck);
      seats.add(seat);
    }
    messages.add('سوليتير تنافسي: لكل لاعب رزمة Klondike مستقلة كاملة.');
  }

  int _rankValue(String card) {
    final rank = card.split('_').first;
    return ranks.indexOf(rank) + 1;
  }

  String _suit(String card) => card.split('_').last;
  bool _red(String card) => const <String>{'D', 'H'}.contains(_suit(card));

  bool _canFoundation(_SolitaireSeat seat, String card) {
    final pile = seat.foundation[_suit(card)]!;
    return _rankValue(card) == pile.length + 1;
  }

  bool _canTableau(_SolitaireColumn column, String card) {
    if (column.up.isEmpty) return _rankValue(card) == 13;
    final top = column.up.last;
    return _red(top) != _red(card) &&
        _rankValue(top) == _rankValue(card) + 1;
  }

  List<Map<String, dynamic>> _actionsFor(int seatIndex) {
    if (gameOver || currentSeat != seatIndex) return <Map<String, dynamic>>[];
    final seat = seats[seatIndex];
    final actions = <Map<String, dynamic>>[];
    if (seat.stock.isNotEmpty) {
      actions.add(<String, dynamic>{'type': 'draw_stock'});
    } else if (seat.waste.isNotEmpty) {
      actions.add(<String, dynamic>{'type': 'recycle_stock'});
    }
    if (seat.waste.isNotEmpty) {
      final card = seat.waste.last;
      if (_canFoundation(seat, card)) {
        actions.add(<String, dynamic>{
          'type': 'move_to_foundation',
          'source': 'waste',
          'card': card,
        });
      }
      for (var to = 0; to < seat.tableau.length; to++) {
        if (_canTableau(seat.tableau[to], card)) {
          actions.add(<String, dynamic>{
            'type': 'move_to_tableau',
            'source': 'waste',
            'card': card,
            'to_column': to,
          });
        }
      }
    }
    for (var from = 0; from < seat.tableau.length; from++) {
      final column = seat.tableau[from];
      if (column.up.isEmpty) continue;
      final top = column.up.last;
      if (_canFoundation(seat, top)) {
        actions.add(<String, dynamic>{
          'type': 'move_to_foundation',
          'source': 'tableau',
          'from_column': from,
          'card': top,
        });
      }
      for (var start = 0; start < column.up.length; start++) {
        final card = column.up[start];
        for (var to = 0; to < seat.tableau.length; to++) {
          if (to == from) continue;
          if (_canTableau(seat.tableau[to], card)) {
            actions.add(<String, dynamic>{
              'type': 'move_to_tableau',
              'source': 'tableau',
              'from_column': from,
              'from_index': start,
              'to_column': to,
              'card': card,
            });
          }
        }
      }
    }
    return actions;
  }

  @override
  void action(String type, Map<String, dynamic> payload) {
    if (currentSeat != 0) _autoBots();
    _apply(0, type, payload);
    _autoBots();
  }

  bool _payloadMatches(Map<String, dynamic> action, Map<String, dynamic> payload) {
    const keys = <String>[
      'source',
      'card',
      'from_column',
      'from_index',
      'to_column',
    ];
    for (final key in keys) {
      if (action.containsKey(key) &&
          action[key].toString() != payload[key].toString()) {
        return false;
      }
    }
    return true;
  }

  void _apply(int seatIndex, String type, Map<String, dynamic> payload) {
    if (gameOver || currentSeat != seatIndex) throw StateError('ليست دورك.');
    final seat = seats[seatIndex];
    final candidate = _actionsFor(seatIndex).cast<Map<String, dynamic>?>().firstWhere(
          (item) => item?['type'] == type &&
              _payloadMatches(item!, payload),
          orElse: () => null,
        );
    if (candidate == null) throw StateError('حركة سوليتير غير قانونية.');
    if (type == 'draw_stock') {
      seat.waste.add(seat.stock.removeLast());
    } else if (type == 'recycle_stock') {
      seat.stock.addAll(seat.waste.reversed);
      seat.waste.clear();
    } else if (type == 'move_to_foundation') {
      final card = _takeSingleSource(seat, candidate);
      seat.foundation[_suit(card)]!.add(card);
      _revealIfNeeded(seat, candidate);
    } else if (type == 'move_to_tableau') {
      final to = int.parse(candidate['to_column'].toString());
      final moved = _takeStackSource(seat, candidate);
      seat.tableau[to].up.addAll(moved);
      _revealIfNeeded(seat, candidate);
    }
    final completed = seat.foundation.values
        .fold<int>(0, (sum, pile) => sum + pile.length);
    messages.add('${_playerKey(seatIndex)} نفّذ $type ($completed/52).');
    if (completed == 52) {
      gameOver = true;
      winnerSeat = seatIndex;
      messages.add('${_playerKey(seatIndex)} أكمل الأسس الأربعة وفاز.');
      return;
    }
    currentSeat = (currentSeat + 1) % _count;
  }

  String _takeSingleSource(
    _SolitaireSeat seat,
    Map<String, dynamic> action,
  ) {
    if (action['source'] == 'waste') return seat.waste.removeLast();
    final from = int.parse(action['from_column'].toString());
    return seat.tableau[from].up.removeLast();
  }

  List<String> _takeStackSource(
    _SolitaireSeat seat,
    Map<String, dynamic> action,
  ) {
    if (action['source'] == 'waste') return <String>[seat.waste.removeLast()];
    final from = int.parse(action['from_column'].toString());
    final column = seat.tableau[from];
    final start = int.parse(
      (action['from_index'] ?? column.up.length - 1).toString(),
    );
    final moved = column.up.sublist(start);
    column.up.removeRange(start, column.up.length);
    return moved;
  }

  void _revealIfNeeded(
    _SolitaireSeat seat,
    Map<String, dynamic> action,
  ) {
    if (action['source'] != 'tableau') return;
    final from = int.parse(action['from_column'].toString());
    final column = seat.tableau[from];
    if (column.up.isEmpty && column.down.isNotEmpty) {
      column.up.add(column.down.removeLast());
    }
  }

  void _autoBots() {
    var guard = 0;
    while (!gameOver && currentSeat != 0 && guard++ < 80) {
      final actions = _actionsFor(currentSeat);
      if (actions.isEmpty) {
        currentSeat = (currentSeat + 1) % _count;
        continue;
      }
      final chosen = actions.firstWhere(
        (item) => item['type'] == 'move_to_foundation',
        orElse: () => actions.firstWhere(
          (item) => item['type'] == 'move_to_tableau',
          orElse: () => actions.first,
        ),
      );
      _apply(
        currentSeat,
        chosen['type'].toString(),
        Map<String, dynamic>.from(chosen)..remove('type'),
      );
    }
  }

  @override
  void timeout() {
    if (currentSeat != 0) {
      _autoBots();
      return;
    }
    final actions = _actionsFor(0);
    if (actions.isEmpty) return;
    final chosen = actions.first;
    _apply(0, chosen['type'].toString(),
        Map<String, dynamic>.from(chosen)..remove('type'));
    _autoBots();
  }

  @override
  Map<String, dynamic> publicState() => <String, dynamic>{
        'phase': gameOver ? 'finished' : 'playing',
        'engine_phase': gameOver ? 'finished' : 'playing',
        'you': 'user:0',
        'hand': <String>[],
        'legal_cards': <String>[],
        'available_actions': _actionsFor(0),
        'current_player': _playerKey(currentSeat),
        'tableau': <String, dynamic>{
          for (var seat = 0; seat < _count; seat++)
            _playerKey(seat): seats[seat]
                .tableau
                .map((column) => <String, dynamic>{
                      'down_count': column.down.length,
                      'up': List<String>.from(column.up),
                    })
                .toList(),
        },
        'waste': <String, dynamic>{
          for (var seat = 0; seat < _count; seat++)
            _playerKey(seat): List<String>.from(seats[seat].waste),
        },
        'foundation': <String, dynamic>{
          for (var seat = 0; seat < _count; seat++)
            _playerKey(seat): <String, List<String>>{
              for (final entry in seats[seat].foundation.entries)
                entry.key: List<String>.from(entry.value),
            },
        },
        'stock_counts': <String, int>{
          for (var seat = 0; seat < _count; seat++)
            _playerKey(seat): seats[seat].stock.length,
        },
        'scores': <String, int>{
          for (var seat = 0; seat < _count; seat++)
            _playerKey(seat): seats[seat]
                .foundation
                .values
                .fold<int>(0, (sum, pile) => sum + pile.length),
        },
        'messages': messages.takeLast(35),
        'game_over': gameOver,
        'winner': winnerSeat == null ? null : _playerKey(winnerSeat!),
        'offline_rules': 'independent_klondike_v187',
      };
}

class _OfflineJackaroo implements OfflineSpecialEngine {
  _OfflineJackaroo({required this.random}) {
    _setup();
    _autoBots();
  }

  final Random random;
  final List<List<String>> hands =
      List<List<String>>.generate(4, (_) => <String>[]);
  final List<List<int>> pieces =
      List<List<int>>.generate(4, (_) => <int>[-1, -1, -1, -1]);
  final List<String> deck = <String>[];
  final List<String> discard = <String>[];
  final List<String> messages = <String>[];
  int currentSeat = 0;
  int dealerSeat = 3;
  int round = 1;
  bool gameOver = false;
  int? winnerTeam;

  static const ranks = <String>[
    'A',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    'J',
    'Q',
    'K',
  ];
  static const suits = <String>['C', 'D', 'S', 'H'];
  static const forward = <String, int>{
    '2': 2,
    '3': 3,
    '5': 5,
    '6': 6,
    '8': 8,
    '9': 9,
    '10': 10,
    'Q': 12,
  };

  @override
  int get playerCount => 4;

  void _setup() {
    deck
      ..clear()
      ..addAll(<String>[
        for (final suit in suits)
          for (final rank in ranks) '${rank}_$suit',
      ])
      ..shuffle(random);
    _dealRound();
    messages.add(
        'جاكارو: A/K للإنزال، A خطوة أو 11، J للتبديل، 7 للتقسيم و4 للخلف.');
  }

  void _dealRound() {
    if (deck.length < 16) {
      deck
        ..addAll(discard)
        ..shuffle(random);
      discard.clear();
    }
    for (final hand in hands) {
      hand.clear();
    }
    for (var card = 0; card < 4; card++) {
      for (var seat = 0; seat < 4; seat++) {
        hands[seat].add(deck.removeLast());
      }
    }
  }

  String _rank(String card) => card.split('_').first;
  int _team(int seat) => seat.isEven ? 0 : 1;
  int _global(int seat, int progress) => (seat * 14 + progress) % 56;
  bool _finished(int seat) => pieces[seat].every((position) => position >= 56);

  int _movableOwner(int seat) {
    if (_finished(seat)) {
      final partner = (seat + 2) % 4;
      if (!_finished(partner)) return partner;
    }
    return seat;
  }

  bool _canStart(int owner) => !pieces[owner].contains(0);

  bool _ownAt(int owner, int progress, int except) {
    for (var index = 0; index < 4; index++) {
      if (index != except && pieces[owner][index] == progress) return true;
    }
    return false;
  }

  bool _protectedOpponentBase(int owner, int progress) {
    final global = _global(owner, progress);
    for (var opponent = 0; opponent < 4; opponent++) {
      if (opponent == owner) continue;
      if (pieces[opponent].any(
        (position) => position == 0 && _global(opponent, 0) == global,
      )) {
        return true;
      }
    }
    return false;
  }

  bool _canMove(int owner, int piece, int steps) {
    final position = pieces[owner][piece];
    if (position < 0) return false;
    final next = position + steps;
    if (next < 0 || next > 59) return false;
    final direction = steps < 0 ? -1 : 1;
    for (var step = position + direction;
        direction > 0 ? step <= next : step >= next;
        step += direction) {
      if (_ownAt(owner, step, piece)) return false;
      if (step < 56 && _protectedOpponentBase(owner, step)) return false;
    }
    return true;
  }

  List<Map<String, dynamic>> _normalActions(
    int owner,
    String card,
    int steps,
  ) {
    final result = <Map<String, dynamic>>[];
    for (var piece = 0; piece < 4; piece++) {
      if (_canMove(owner, piece, steps)) {
        result.add(<String, dynamic>{
          'type': 'play_card',
          'card': card,
          'owner': _playerKey(owner),
          'piece': piece,
          'steps': steps,
          'label': 'الحجر ${piece + 1} ${steps < 0 ? 'للخلف' : 'للأمام'} ${steps.abs()}',
        });
      }
    }
    return result;
  }

  List<Map<String, dynamic>> _actionsFor(int seat) {
    if (gameOver || currentSeat != seat) return <Map<String, dynamic>>[];
    final owner = _movableOwner(seat);
    final result = <Map<String, dynamic>>[];
    for (final card in hands[seat]) {
      final rank = _rank(card);
      if (const <String>{'A', 'K'}.contains(rank)) {
        if (_canStart(owner)) {
          for (var piece = 0; piece < 4; piece++) {
            if (pieces[owner][piece] < 0) {
              result.add(<String, dynamic>{
                'type': 'play_card',
                'card': card,
                'owner': _playerKey(owner),
                'piece': piece,
                'steps': 0,
                'label': 'إنزال الحجر ${piece + 1}',
              });
            }
          }
        }
        if (rank == 'K') continue;
      }
      if (rank == 'J') {
        result.addAll(_swapActions(seat, owner, card));
        continue;
      }
      if (rank == '7') {
        result.addAll(_normalActions(owner, card, 7));
        result.addAll(_splitSevenActions(owner, card));
        continue;
      }
      if (rank == 'A') {
        result.addAll(_normalActions(owner, card, 1));
        result.addAll(_normalActions(owner, card, 11));
      } else {
        final steps = rank == '4' ? -4 : (forward[rank] ?? 0);
        if (steps != 0) result.addAll(_normalActions(owner, card, steps));
      }
    }
    if (result.isEmpty && hands[seat].isNotEmpty) {
      return <Map<String, dynamic>>[
        <String, dynamic>{'type': 'pass'},
      ];
    }
    return result;
  }

  List<Map<String, dynamic>> _swapActions(
    int seat,
    int owner,
    String card,
  ) {
    final result = <Map<String, dynamic>>[];
    for (var piece = 0; piece < 4; piece++) {
      final position = pieces[owner][piece];
      if (position <= 0 || position >= 56) continue;
      for (var opponent = 0; opponent < 4; opponent++) {
        if (opponent == owner || _team(opponent) == _team(seat)) continue;
        for (var target = 0; target < 4; target++) {
          final targetPosition = pieces[opponent][target];
          if (targetPosition <= 0 || targetPosition >= 56) continue;
          result.add(<String, dynamic>{
            'type': 'play_card',
            'card': card,
            'owner': _playerKey(owner),
            'piece': piece,
            'target_owner': _playerKey(opponent),
            'target_piece': target,
            'label': 'تبديل الحجر ${piece + 1} مع الخصم',
          });
        }
      }
    }
    return result;
  }

  List<Map<String, dynamic>> _splitSevenActions(int owner, String card) {
    final result = <Map<String, dynamic>>[];
    for (var first = 0; first < 4; first++) {
      for (var second = 0; second < 4; second++) {
        if (first == second) continue;
        for (var firstSteps = 1; firstSteps <= 6; firstSteps++) {
          final secondSteps = 7 - firstSteps;
          if (!_canMove(owner, first, firstSteps)) continue;
          final snapshot = pieces
              .map((values) => List<int>.from(values))
              .toList(growable: false);
          final messageCount = messages.length;
          _execute(owner, first, firstSteps);
          final secondLegal = _canMove(owner, second, secondSteps);
          for (var seat = 0; seat < 4; seat++) {
            pieces[seat]
              ..clear()
              ..addAll(snapshot[seat]);
          }
          if (messages.length > messageCount) {
            messages.removeRange(messageCount, messages.length);
          }
          if (!secondLegal) continue;
          result.add(<String, dynamic>{
            'type': 'play_card',
            'card': card,
            'owner': _playerKey(owner),
            'piece': first,
            'steps': firstSteps,
            'piece2': second,
            'steps2': secondSteps,
            'label':
                'تقسيم $firstSteps + $secondSteps على ${first + 1} و${second + 1}',
          });
        }
      }
    }
    return result;
  }

  int _seatFromKey(Object? value) {
    final key = value?.toString() ?? '';
    if (key == 'user:0') return 0;
    return int.tryParse(key.replaceFirst('bot:', '')) ?? -1;
  }

  bool _matches(Map<String, dynamic> action, Map<String, dynamic> payload) {
    const keys = <String>[
      'card',
      'owner',
      'piece',
      'steps',
      'piece2',
      'steps2',
      'target_owner',
      'target_piece',
    ];
    for (final key in keys) {
      if (action.containsKey(key) &&
          action[key].toString() != payload[key].toString()) {
        return false;
      }
    }
    return true;
  }

  @override
  void action(String type, Map<String, dynamic> payload) {
    if (currentSeat != 0) _autoBots();
    _apply(0, type, payload);
    _autoBots();
  }

  void _apply(int seat, String type, Map<String, dynamic> payload) {
    if (gameOver || currentSeat != seat) throw StateError('ليست دورك.');
    final actions = _actionsFor(seat);
    if (type == 'pass') {
      if (!actions.any((item) => item['type'] == 'pass')) {
        throw StateError('توجد حركة قانونية ولا يمكن إسقاط الورقة.');
      }
      discard.add(hands[seat].removeAt(0));
      messages.add('${_playerKey(seat)} أسقط ورقة بلا حركة قانونية.');
      _finishTurn();
      return;
    }
    if (type != 'play_card') throw StateError('حركة جاكارو غير معروفة.');
    final candidate = actions.cast<Map<String, dynamic>?>().firstWhere(
          (item) => item?['type'] == 'play_card' && _matches(item!, payload),
          orElse: () => null,
        );
    if (candidate == null) throw StateError('اختر وظيفة قانونية للورقة.');
    final card = candidate['card'].toString();
    final owner = _seatFromKey(candidate['owner']);
    final rank = _rank(card);
    if (rank == 'J') {
      final piece = int.parse(candidate['piece'].toString());
      final targetOwner = _seatFromKey(candidate['target_owner']);
      final targetPiece = int.parse(candidate['target_piece'].toString());
      final temp = pieces[owner][piece];
      pieces[owner][piece] = pieces[targetOwner][targetPiece];
      pieces[targetOwner][targetPiece] = temp;
    } else if (rank == '7' && candidate.containsKey('piece2')) {
      _execute(
        owner,
        int.parse(candidate['piece'].toString()),
        int.parse(candidate['steps'].toString()),
      );
      _execute(
        owner,
        int.parse(candidate['piece2'].toString()),
        int.parse(candidate['steps2'].toString()),
      );
    } else {
      final piece = int.parse(candidate['piece'].toString());
      final steps = int.tryParse(candidate['steps']?.toString() ?? '') ?? 0;
      if (pieces[owner][piece] < 0) {
        pieces[owner][piece] = 0;
        _capture(owner, piece, 0);
      } else {
        _execute(owner, piece, steps);
      }
    }
    hands[seat].remove(card);
    discard.add(card);
    messages.add('${_playerKey(seat)} لعب $card.');
    final team = _team(seat);
    final teamMembers = team == 0 ? const <int>[0, 2] : const <int>[1, 3];
    if (teamMembers.every(_finished)) {
      gameOver = true;
      winnerTeam = team;
      messages.add('فاز فريق ${team == 0 ? 'A' : 'B'} بإدخال الأحجار الثمانية.');
      return;
    }
    _finishTurn();
  }

  void _execute(int owner, int piece, int steps) {
    final next = pieces[owner][piece] + steps;
    pieces[owner][piece] = next;
    _capture(owner, piece, next);
  }

  void _capture(int owner, int piece, int progress) {
    if (progress <= 0 || progress >= 56) return;
    final global = _global(owner, progress);
    for (var opponent = 0; opponent < 4; opponent++) {
      if (opponent == owner) continue;
      for (var target = 0; target < 4; target++) {
        final targetProgress = pieces[opponent][target];
        if (targetProgress <= 0 || targetProgress >= 56) continue;
        if (_global(opponent, targetProgress) == global) {
          pieces[opponent][target] = -1;
          messages.add('${_playerKey(owner)} أعاد حجر خصم إلى البيت.');
        }
      }
    }
  }

  void _finishTurn() {
    if (hands.every((hand) => hand.isEmpty)) {
      round++;
      dealerSeat = (dealerSeat + 1) % 4;
      _dealRound();
      currentSeat = (dealerSeat + 1) % 4;
      return;
    }
    currentSeat = (currentSeat + 1) % 4;
  }

  void _autoBots() {
    var guard = 0;
    while (!gameOver && currentSeat != 0 && guard++ < 100) {
      final actions = _actionsFor(currentSeat);
      final playable =
          actions.where((item) => item['type'] == 'play_card').toList();
      final chosen = playable.isEmpty
          ? actions.first
          : (playable
                ..sort((a, b) {
                  final ascore = (a.containsKey('target_owner') ? 100 : 0) +
                      (a.containsKey('piece2') ? 40 : 0) +
                      (int.tryParse(a['steps']?.toString() ?? '') ?? 0).abs();
                  final bscore = (b.containsKey('target_owner') ? 100 : 0) +
                      (b.containsKey('piece2') ? 40 : 0) +
                      (int.tryParse(b['steps']?.toString() ?? '') ?? 0).abs();
                  return bscore.compareTo(ascore);
                }))
              .first;
      _apply(
        currentSeat,
        chosen['type'].toString(),
        Map<String, dynamic>.from(chosen)
          ..remove('type')
          ..remove('label'),
      );
    }
  }

  @override
  void timeout() {
    if (currentSeat != 0) {
      _autoBots();
      return;
    }
    final actions = _actionsFor(0);
    if (actions.isEmpty) return;
    final chosen = actions.first;
    _apply(
      0,
      chosen['type'].toString(),
      Map<String, dynamic>.from(chosen)
        ..remove('type')
        ..remove('label'),
    );
    _autoBots();
  }

  @override
  Map<String, dynamic> publicState() => <String, dynamic>{
        'phase': gameOver ? 'finished' : 'playing',
        'engine_phase': gameOver ? 'finished' : 'playing',
        'hand': List<String>.from(hands[0]),
        'legal_cards': _actionsFor(0)
            .where((item) => item['type'] == 'play_card')
            .map((item) => item['card'].toString())
            .toSet()
            .toList(),
        'available_actions': _actionsFor(0),
        'current_player': _playerKey(currentSeat),
        'pieces': <String, List<int>>{
          for (var seat = 0; seat < 4; seat++)
            _playerKey(seat): List<int>.from(pieces[seat]),
        },
        'scores': <String, int>{
          'teamA': pieces[0].where((p) => p >= 56).length +
              pieces[2].where((p) => p >= 56).length,
          'teamB': pieces[1].where((p) => p >= 56).length +
              pieces[3].where((p) => p >= 56).length,
        },
        'round': round,
        'messages': messages.takeLast(35),
        'game_over': gameOver,
        'winner': winnerTeam == null ? null : (winnerTeam == 0 ? 'teamA' : 'teamB'),
        'offline_rules': 'jackaroo_partnership_v187',
      };
}

class _OfflineLeekha implements OfflineSpecialEngine {
  _OfflineLeekha({required this.random}) {
    _startRound();
  }

  final Random random;
  final List<List<String>> hands =
      List<List<String>>.generate(4, (_) => <String>[]);
  final Map<int, String> trick = <int, String>{};
  final List<int> score = <int>[0, 0, 0, 0];
  final List<int> roundPenalty = <int>[0, 0, 0, 0];
  final List<String> messages = <String>[];
  final Map<int, List<String>> pendingPasses = <int, List<String>>{};
  int currentSeat = 0;
  int dealerSeat = 3;
  int round = 0;
  String phase = 'passing';
  String passDirection = 'right';
  bool gameOver = false;
  int? winnerTeam;

  static const ranks = <String>[
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '10',
    'J',
    'Q',
    'K',
    'A',
  ];
  static const suits = <String>['C', 'D', 'S', 'H'];

  @override
  int get playerCount => 4;

  void _startRound() {
    if (round > 0) dealerSeat = (dealerSeat + 1) % 4;
    round++;
    phase = 'passing';
    passDirection = round.isOdd ? 'right' : 'left';
    currentSeat = 0;
    trick.clear();
    pendingPasses.clear();
    for (var seat = 0; seat < 4; seat++) {
      hands[seat].clear();
      roundPenalty[seat] = 0;
    }
    final deck = <String>[
      for (final suit in suits)
        for (final rank in ranks) '${rank}_$suit',
    ]..shuffle(random);
    for (var card = 0; card < 13; card++) {
      for (var seat = 0; seat < 4; seat++) {
        hands[seat].add(deck.removeLast());
      }
    }
    for (final hand in hands) {
      hand.sort((a, b) {
        final suit = _suit(a).compareTo(_suit(b));
        return suit != 0 ? suit : _rankValue(a).compareTo(_rankValue(b));
      });
    }
    messages.add(
        'جولة $round: مرّر 3 أوراق ${passDirection == 'right' ? 'لليمين' : 'لليسار'}.');
  }

  String _rank(String card) => card.split('_').first;
  String _suit(String card) => card.split('_').last;
  int _rankValue(String card) => ranks.indexOf(_rank(card));
  int _passTarget(int seat) =>
      passDirection == 'right' ? (seat + 1) % 4 : (seat + 3) % 4;

  List<String> _legalCards(int seat) {
    if (phase != 'playing' || currentSeat != seat) return <String>[];
    if (trick.isEmpty) return List<String>.from(hands[seat]);
    final leadSuit = _suit(trick.values.first);
    final follow =
        hands[seat].where((card) => _suit(card) == leadSuit).toList();
    if (follow.isNotEmpty) return follow;
    final forced = hands[seat]
        .where((card) => card == 'Q_S' || card == '10_D')
        .toList();
    return forced.isNotEmpty ? forced : List<String>.from(hands[seat]);
  }

  List<Map<String, dynamic>> _actionsFor(int seat) {
    if (gameOver || currentSeat != seat) return <Map<String, dynamic>>[];
    if (phase == 'passing') {
      return seat == 0
          ? <Map<String, dynamic>>[
              <String, dynamic>{
                'type': 'pass_cards',
                'count': 3,
                'direction': passDirection,
              },
            ]
          : <Map<String, dynamic>>[];
    }
    return <Map<String, dynamic>>[
      for (final card in _legalCards(seat))
        <String, dynamic>{'type': 'play_card', 'card': card},
    ];
  }

  @override
  void action(String type, Map<String, dynamic> payload) {
    if (currentSeat != 0) _autoBots();
    if (phase == 'passing') {
      if (type != 'pass_cards') throw StateError('اختر ثلاث أوراق للتمرير.');
      final cards = (payload['cards'] as List?)
              ?.map((item) => item.toString())
              .toList() ??
          <String>[];
      _submitPass(0, cards);
      for (var seat = 1; seat < 4; seat++) {
        _submitPass(seat, _botPassChoice(seat));
      }
      _completePasses();
      _autoBots();
      return;
    }
    _play(0, type, payload);
    _autoBots();
  }

  void _submitPass(int seat, List<String> cards) {
    if (cards.length != 3 || cards.toSet().length != 3) {
      throw StateError('يجب اختيار ثلاث أوراق مختلفة.');
    }
    final copy = List<String>.from(hands[seat]);
    for (final card in cards) {
      if (!copy.remove(card)) throw StateError('إحدى الأوراق ليست في يدك.');
    }
    pendingPasses[seat] = List<String>.from(cards);
  }

  List<String> _botPassChoice(int seat) {
    final copy = List<String>.from(hands[seat]);
    copy.sort((a, b) {
      int danger(String card) {
        if (card == 'Q_S') return 100;
        if (card == '10_D') return 90;
        if (_suit(card) == 'H') return 40 + _rankValue(card);
        return _rankValue(card);
      }

      return danger(b).compareTo(danger(a));
    });
    return copy.take(3).toList();
  }

  void _completePasses() {
    if (pendingPasses.length != 4) return;
    for (var seat = 0; seat < 4; seat++) {
      for (final card in pendingPasses[seat]!) {
        hands[seat].remove(card);
      }
    }
    for (var seat = 0; seat < 4; seat++) {
      hands[_passTarget(seat)].addAll(pendingPasses[seat]!);
    }
    pendingPasses.clear();
    phase = 'playing';
    currentSeat = (dealerSeat + 1) % 4;
    messages.add('اكتمل تمرير الأوراق وبدأ اللعب.');
  }

  void _play(int seat, String type, Map<String, dynamic> payload) {
    if (type != 'play_card' || phase != 'playing' || currentSeat != seat) {
      throw StateError('ليست حركة ليخة قانونية.');
    }
    final card = payload['card']?.toString() ?? '';
    if (!_legalCards(seat).contains(card) || !hands[seat].remove(card)) {
      throw StateError('اتبع النوع، وإن لم تملكه يجب رمي الليخة أولاً.');
    }
    trick[seat] = card;
    messages.add('${_playerKey(seat)} لعب $card.');
    if (trick.length == 4) {
      final winner = _trickWinner();
      final penalty = trick.values.fold<int>(0, (sum, item) {
        if (item == 'Q_S') return sum + 13;
        if (item == '10_D') return sum + 10;
        if (_suit(item) == 'H') return sum + 1;
        return sum;
      });
      score[winner] += penalty;
      roundPenalty[winner] += penalty;
      messages.add('${_playerKey(winner)} أخذ اللمّة وعليه $penalty نقطة.');
      trick.clear();
      currentSeat = winner;
      if (hands.every((hand) => hand.isEmpty)) {
        _finishRound();
      }
    } else {
      currentSeat = (seat + 1) % 4;
    }
  }

  int _trickWinner() {
    final leadSuit = _suit(trick.values.first);
    var winner = trick.keys.first;
    var best = trick.values.first;
    for (final entry in trick.entries.skip(1)) {
      if (_suit(entry.value) == leadSuit &&
          _rankValue(entry.value) > _rankValue(best)) {
        winner = entry.key;
        best = entry.value;
      }
    }
    return winner;
  }

  void _finishRound() {
    final teamA = score[0] + score[2];
    final teamB = score[1] + score[3];
    messages.add('انتهت الجولة: فريق A $teamA — فريق B $teamB.');
    if (score.reduce(max) >= 101) {
      gameOver = true;
      phase = 'finished';
      winnerTeam = teamA <= teamB ? 0 : 1;
      messages.add('الفائز هو الفريق الأقل عقوبة.');
    } else {
      _startRound();
    }
  }

  void _autoBots() {
    var guard = 0;
    while (!gameOver && phase == 'playing' && currentSeat != 0 && guard++ < 100) {
      final legal = _legalCards(currentSeat);
      if (legal.isEmpty) break;
      legal.sort((a, b) {
        int danger(String card) {
          if (card == 'Q_S') return 100;
          if (card == '10_D') return 90;
          if (_suit(card) == 'H') return 40 + _rankValue(card);
          return _rankValue(card);
        }

        return danger(a).compareTo(danger(b));
      });
      _play(currentSeat, 'play_card', <String, dynamic>{'card': legal.first});
    }
  }

  @override
  void timeout() {
    if (phase == 'passing') {
      _submitPass(0, _botPassChoice(0));
      for (var seat = 1; seat < 4; seat++) {
        _submitPass(seat, _botPassChoice(seat));
      }
      _completePasses();
      _autoBots();
      return;
    }
    if (currentSeat != 0) {
      _autoBots();
      return;
    }
    final legal = _legalCards(0);
    if (legal.isNotEmpty) {
      _play(0, 'play_card', <String, dynamic>{'card': legal.first});
      _autoBots();
    }
  }

  @override
  Map<String, dynamic> publicState() {
    final teamA = score[0] + score[2];
    final teamB = score[1] + score[3];
    return <String, dynamic>{
      'phase': phase,
      'engine_phase': phase,
      'hand': List<String>.from(hands[0]),
      'legal_cards': _legalCards(0),
      'available_actions': _actionsFor(0),
      'current_player': _playerKey(currentSeat),
      'trick': <String, String>{
        for (final entry in trick.entries)
          _playerKey(entry.key): entry.value,
      },
      'scores': <String, int>{
        for (var seat = 0; seat < 4; seat++) _playerKey(seat): score[seat],
      },
      'team_scores': <String, int>{'teamA': teamA, 'teamB': teamB},
      'round_penalties': <String, int>{
        for (var seat = 0; seat < 4; seat++)
          _playerKey(seat): roundPenalty[seat],
      },
      'pass_direction': passDirection,
      'round': round,
      'messages': messages.takeLast(35),
      'game_over': gameOver,
      'winner': winnerTeam == null ? null : (winnerTeam == 0 ? 'teamA' : 'teamB'),
      'offline_rules': 'leekha_partnership_101_v187',
    };
  }
}

extension _TakeLastExtension<T> on Iterable<T> {
  List<T> takeLast(int count) {
    final values = toList(growable: false);
    if (values.length <= count) return values;
    return values.sublist(values.length - count);
  }
}
