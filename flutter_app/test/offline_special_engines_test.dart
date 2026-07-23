import 'package:flutter_test/flutter_test.dart';
import 'package:warqna_mobile/engines/local_game_engine.dart';

Map<String, dynamic> stateOf(LocalGameSession session) =>
    Map<String, dynamic>.from(session.room()['state'] as Map);

void main() {
  test('Domino is playable offline with a legal double-six state', () {
    final session = LocalGameSession(
      gameId: 'domino',
      humanName: 'Tester',
      playerCount: 2,
      seed: 18701,
    );
    final state = stateOf(session);
    expect(state['hand'], isA<List>());
    expect((state['hand'] as List).length, greaterThanOrEqualTo(7));
    expect(state['available_actions'], isNotEmpty);
    expect(state['offline_rules'], 'double_six_to_100_v187');
  });

  test('Backgammon enforces roll then legal checker movement offline', () {
    final session = LocalGameSession(
      gameId: 'backgammon',
      humanName: 'Tester',
      seed: 18702,
    );
    var state = stateOf(session);
    expect((state['available_actions'] as List).first['type'], 'roll');
    session.action('roll', const <String, dynamic>{});
    state = stateOf(session);
    expect(state['dice'], hasLength(2));
    expect(state['available_actions'], isNotEmpty);
    expect(state['offline_rules'], 'backgammon_max_dice_v187');
  });

  test('Competitive Solitaire gives every player an independent Klondike', () {
    final session = LocalGameSession(
      gameId: 'solitaire_multiplayer',
      humanName: 'Tester',
      playerCount: 4,
      seed: 18703,
    );
    final state = stateOf(session);
    final tableau = Map<String, dynamic>.from(state['tableau'] as Map);
    expect(tableau.keys, hasLength(4));
    expect(tableau['user:0'], hasLength(7));
    expect(
      Map<String, dynamic>.from(state['stock_counts'] as Map)['user:0'],
      24,
    );
    expect(state['offline_rules'], 'independent_klondike_v187');
  });

  test('Jackaroo exposes card abilities and four pieces per player offline', () {
    final session = LocalGameSession(
      gameId: 'jackaroo',
      humanName: 'Tester',
      seed: 18704,
    );
    final state = stateOf(session);
    expect(state['hand'], hasLength(4));
    final pieces = Map<String, dynamic>.from(state['pieces'] as Map);
    expect(pieces.keys, hasLength(4));
    expect(pieces['user:0'], hasLength(4));
    expect(state['available_actions'], isNotEmpty);
    expect(state['offline_rules'], 'jackaroo_partnership_v187');
  });

  test('Leekha passes exactly three cards then starts legal play', () {
    final session = LocalGameSession(
      gameId: 'leekha',
      humanName: 'Tester',
      seed: 18705,
    );
    var state = stateOf(session);
    expect(state['phase'], 'passing');
    final cards = (state['hand'] as List).take(3).toList();
    session.action('pass_cards', <String, dynamic>{'cards': cards});
    state = stateOf(session);
    expect(state['phase'], 'playing');
    expect(state['hand'], hasLength(13));
    expect(state['available_actions'], isNotEmpty);
    expect(state['offline_rules'], 'leekha_partnership_101_v187');
  });

  test('Classic Banakil no longer falls through to a trick engine offline', () {
    final session = LocalGameSession(
      gameId: 'pinochle',
      humanName: 'Tester',
      playerCount: 4,
      seed: 18706,
    );
    final state = stateOf(session);
    expect(state['phase'], 'discard');
    expect(state['hand'], hasLength(19));
    expect(
      (state['available_actions'] as List)
          .map((action) => (action as Map)['type'])
          .toSet(),
      contains('discard'),
    );
  });

  test('Offline Hand requires a meld after drawing the exposed discard', () {
    final session = LocalGameSession(
      gameId: 'hand',
      humanName: 'Tester',
      playerCount: 2,
      seed: 18707,
    );
    var state = stateOf(session);
    final openingDiscard = (state['available_actions'] as List)
        .cast<Map>()
        .firstWhere((action) => action['type'] == 'discard');
    session.action(
      'discard',
      <String, dynamic>{'card': openingDiscard['card']},
    );
    state = stateOf(session);
    expect(state['phase'], 'draw');
    session.action('draw_discard', const <String, dynamic>{});
    state = stateOf(session);
    final card = (state['hand'] as List).first.toString();
    expect(
      () => session.action('discard', <String, dynamic>{'card': card}),
      throwsStateError,
    );
  });
}
