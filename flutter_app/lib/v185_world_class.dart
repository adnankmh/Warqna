part of 'main.dart';

/// V185 turns the inherited table catalog into a first-class, responsive
/// marketplace. The catalog remains additive: all legacy, V173 and reference
/// tables stay available, while the 40 tables supplied in the new design are
/// presented as the opening collection.
const Color warqnaV185Background = Color(0xff020d09);
const Color warqnaV185Panel = Color(0xff071a13);
const Color warqnaV185PanelRaised = Color(0xff0b241a);
const Color warqnaV185Gold = Color(0xfff0bd55);
const Color warqnaV185GoldLight = Color(0xffffdf8a);
const Color warqnaV185Emerald = Color(0xff1fb176);
const String warqnaReferenceCatalogAssetV185 = 'assets/images/tables/reference/reference_catalog_v185.png';

Future<ui.Image>? _warqnaReferenceCatalogImageV185;

Future<ui.Image> _loadWarqnaReferenceCatalogV185() {
  return _warqnaReferenceCatalogImageV185 ??= () async {
    final data = await rootBundle.load(warqnaReferenceCatalogAssetV185);
    final bytes = data.buffer.asUint8List(data.offsetInBytes, data.lengthInBytes);
    final codec = await ui.instantiateImageCodec(bytes);
    try {
      return (await codec.getNextFrame()).image;
    } finally {
      codec.dispose();
    }
  }();
}

int? _referenceTableIndexV185(String productId) {
  const prefix = 'table_reference_';
  if (!productId.startsWith(prefix)) return null;
  return int.tryParse(productId.substring(prefix.length));
}

/// Source rectangles for the approved table art in the user-supplied design.
/// Tables 1–11 already have clean standalone HD exports; 12–40 use these
/// exact catalog crops so inherited transparent/undersized exports never show.
Rect? referenceTableCatalogRectV185(String productId) {
  final index = _referenceTableIndexV185(productId);
  if (index == null || index < 12 || index > 40) return null;
  const topX = <double>[644, 821, 998, 1175, 1352];
  if (index <= 20) {
    final column = (index - 11) % 5;
    final y = index <= 15 ? 389.0 : 510.0;
    return Rect.fromLTWH(topX[column], y, 158, 64);
  }
  if (index <= 30) {
    const leftX = <double>[21, 181, 342, 503, 658];
    const leftWidth = <double>[143, 143, 143, 143, 98];
    final column = (index - 21) % 5;
    final y = index <= 25 ? 675.0 : 790.0;
    return Rect.fromLTWH(leftX[column], y, leftWidth[column], 58);
  }
  const rightX = <double>[781, 934, 1087, 1240, 1393];
  final column = (index - 31) % 5;
  final y = index <= 35 ? 675.0 : 790.0;
  return Rect.fromLTWH(rightX[column], y, 143, 58);
}

class ReferenceCatalogTablePreviewV185 extends StatelessWidget {
  final StoreProduct product;
  final Rect sourceRect;

  const ReferenceCatalogTablePreviewV185({
    super.key,
    required this.product,
    required this.sourceRect,
  });

  @override
  Widget build(BuildContext context) => FutureBuilder<ui.Image>(
        future: _loadWarqnaReferenceCatalogV185(),
        builder: (context, snapshot) {
          if (snapshot.hasData) {
            return SizedBox.expand(
              child: CustomPaint(
                painter: _ReferenceCatalogPainterV185(snapshot.requireData, sourceRect),
              ),
            );
          }
          if (snapshot.hasError && product.imageAsset != null) {
            return Image.asset(product.imageAsset!, fit: BoxFit.contain, filterQuality: FilterQuality.high);
          }
          return const Center(
            child: SizedBox.square(
              dimension: 24,
              child: CircularProgressIndicator(strokeWidth: 2, color: warqnaV185Gold),
            ),
          );
        },
      );
}

class WarqnaTableAssetV185 extends StatelessWidget {
  final StoreProduct product;
  final FilterQuality filterQuality;
  final double fallbackFontSize;

  const WarqnaTableAssetV185({
    super.key,
    required this.product,
    required this.filterQuality,
    required this.fallbackFontSize,
  });

  @override
  Widget build(BuildContext context) {
    final sourceRect = referenceTableCatalogRectV185(product.id);
    if (sourceRect != null) {
      return ReferenceCatalogTablePreviewV185(product: product, sourceRect: sourceRect);
    }
    return Image.asset(
      product.imageAsset!,
      fit: BoxFit.contain,
      alignment: Alignment.center,
      filterQuality: filterQuality,
      errorBuilder: (_, __, ___) => Center(child: Text(product.icon, style: TextStyle(fontSize: fallbackFontSize))),
    );
  }
}

class _ReferenceCatalogPainterV185 extends CustomPainter {
  final ui.Image image;
  final Rect sourceRect;

  const _ReferenceCatalogPainterV185(this.image, this.sourceRect);

  @override
  void paint(Canvas canvas, Size size) {
    if (size.isEmpty) return;
    final fitted = applyBoxFit(BoxFit.contain, sourceRect.size, size);
    final destination = Alignment.center.inscribe(fitted.destination, Offset.zero & size);
    canvas.drawImageRect(
      image,
      sourceRect,
      destination,
      Paint()
        ..isAntiAlias = true
        ..filterQuality = FilterQuality.high,
    );
  }

  @override
  bool shouldRepaint(covariant _ReferenceCatalogPainterV185 oldDelegate) {
    return oldDelegate.image != image || oldDelegate.sourceRect != sourceRect;
  }
}

String warqnaV185Text(String language, String key) {
  const values = <String, Map<String, String>>{
    'tables': <String, String>{'ar': 'الطاولات', 'en': 'Tables', 'de': 'Tische', 'tr': 'Masalar', 'fr': 'Tables', 'es': 'Mesas'},
    'subtitle': <String, String>{'ar': 'اختر طاولتك المفضلة واستمتع بلعب أكثر فخامة', 'en': 'Choose your favorite table and play in premium style', 'de': 'Wähle deinen Lieblingstisch und spiele stilvoll', 'tr': 'Favori masanı seç ve premium tarzda oyna', 'fr': 'Choisissez votre table préférée et jouez avec élégance', 'es': 'Elige tu mesa favorita y juega con estilo'},
    'allStore': <String, String>{'ar': 'كل المتجر', 'en': 'Full store', 'de': 'Gesamter Shop', 'tr': 'Tüm mağaza', 'fr': 'Toute la boutique', 'es': 'Toda la tienda'},
    'inventory': <String, String>{'ar': 'مقتنياتي', 'en': 'My items', 'de': 'Meine Artikel', 'tr': 'Eşyalarım', 'fr': 'Mes objets', 'es': 'Mis objetos'},
    'all': <String, String>{'ar': 'الكل', 'en': 'All', 'de': 'Alle', 'tr': 'Tümü', 'fr': 'Tout', 'es': 'Todo'},
    'modern': <String, String>{'ar': 'حديثة', 'en': 'New collection', 'de': 'Neue Kollektion', 'tr': 'Yeni koleksiyon', 'fr': 'Nouvelle collection', 'es': 'Nueva colección'},
    'luxury': <String, String>{'ar': 'فاخرة', 'en': 'Royal', 'de': 'Königlich', 'tr': 'Kraliyet', 'fr': 'Royale', 'es': 'Real'},
    'special': <String, String>{'ar': 'خاصة', 'en': 'Showcase', 'de': 'Showcase', 'tr': 'Vitrin', 'fr': 'Vitrine', 'es': 'Especiales'},
    'classic': <String, String>{'ar': 'كلاسيكية', 'en': 'Classic', 'de': 'Klassisch', 'tr': 'Klasik', 'fr': 'Classique', 'es': 'Clásica'},
    'search': <String, String>{'ar': 'ابحث باسم الطاولة…', 'en': 'Search tables…', 'de': 'Tische suchen…', 'tr': 'Masa ara…', 'fr': 'Rechercher une table…', 'es': 'Buscar mesas…'},
    'exclusive': <String, String>{'ar': 'تصاميم حصرية', 'en': 'Exclusive designs', 'de': 'Exklusive Designs', 'tr': 'Özel tasarımlar', 'fr': 'Designs exclusifs', 'es': 'Diseños exclusivos'},
    'quality': <String, String>{'ar': 'جودة عالية', 'en': 'High quality', 'de': 'Hohe Qualität', 'tr': 'Yüksek kalite', 'fr': 'Haute qualité', 'es': 'Alta calidad'},
    'effects': <String, String>{'ar': 'تأثيرات فاخرة', 'en': 'Premium effects', 'de': 'Premium-Effekte', 'tr': 'Premium efektler', 'fr': 'Effets premium', 'es': 'Efectos premium'},
    'prices': <String, String>{'ar': 'أسعار متنوعة', 'en': 'Flexible prices', 'de': 'Flexible Preise', 'tr': 'Esnek fiyatlar', 'fr': 'Prix variés', 'es': 'Precios variados'},
    'featured': <String, String>{'ar': 'الطاولة المختارة', 'en': 'Selected table', 'de': 'Ausgewählter Tisch', 'tr': 'Seçili masa', 'fr': 'Table sélectionnée', 'es': 'Mesa seleccionada'},
    'preview': <String, String>{'ar': 'معاينة كاملة', 'en': 'Full preview', 'de': 'Vollständige Vorschau', 'tr': 'Tam önizleme', 'fr': 'Aperçu complet', 'es': 'Vista completa'},
    'buy': <String, String>{'ar': 'شراء', 'en': 'Buy', 'de': 'Kaufen', 'tr': 'Satın al', 'fr': 'Acheter', 'es': 'Comprar'},
    'activate': <String, String>{'ar': 'تفعيل', 'en': 'Activate', 'de': 'Aktivieren', 'tr': 'Etkinleştir', 'fr': 'Activer', 'es': 'Activar'},
    'active': <String, String>{'ar': 'مفعّلة', 'en': 'Active', 'de': 'Aktiv', 'tr': 'Etkin', 'fr': 'Active', 'es': 'Activa'},
    'owned': <String, String>{'ar': 'مملوكة', 'en': 'Owned', 'de': 'Im Besitz', 'tr': 'Sahip', 'fr': 'Possédée', 'es': 'Comprada'},
    'empty': <String, String>{'ar': 'لا توجد طاولات مطابقة. جرّب بحثًا أو تصنيفًا آخر.', 'en': 'No matching tables. Try another search or filter.', 'de': 'Keine passenden Tische. Ändere Suche oder Filter.', 'tr': 'Eşleşen masa yok. Aramayı veya filtreyi değiştir.', 'fr': 'Aucune table correspondante. Modifiez la recherche ou le filtre.', 'es': 'No hay mesas coincidentes. Cambia la búsqueda o el filtro.'},
    'security': <String, String>{'ar': 'الشراء والتفعيل في النسخة المنشورة يعتمدان من الخادم، ولا يمكن للواجهة تعديل الرصيد أو الملكية.', 'en': 'Production purchases and activations are server-authoritative; the client cannot change balance or ownership.', 'de': 'Käufe und Aktivierungen werden in Produktion vom Server bestätigt.', 'tr': 'Üretimde satın alma ve etkinleştirme sunucu tarafından doğrulanır.', 'fr': 'En production, achats et activations sont validés par le serveur.', 'es': 'En producción, compras y activaciones son validadas por el servidor.'},
  };
  final entry = values[key];
  if (entry == null) return key;
  return entry[language] ?? entry['en'] ?? key;
}

class WorldClassTableStoreV185 extends StatefulWidget {
  final AppController controller;
  final ValueChanged<String> onOpenStoreCategory;

  const WorldClassTableStoreV185({
    super.key,
    required this.controller,
    required this.onOpenStoreCategory,
  });

  @override
  State<WorldClassTableStoreV185> createState() => _WorldClassTableStoreV185State();
}

class _WorldClassTableStoreV185State extends State<WorldClassTableStoreV185> {
  String scope = 'reference';
  String batch = 'all';
  String tier = 'all';
  String query = '';
  String? focusedProductId;

  bool _matchesScope(StoreProduct product) {
    switch (scope) {
      case 'reference':
        return product.id.startsWith('table_reference_');
      case 'royal':
        return product.collection == 'v173_royal';
      case 'showcase':
        return product.collection == 'v173_showcase';
      case 'classic':
        return !product.id.startsWith('table_reference_') &&
            product.collection != 'v173_royal' &&
            product.collection != 'v173_showcase';
      default:
        return true;
    }
  }

  int _sortWeight(StoreProduct product) {
    if (product.id.startsWith('table_reference_')) return 0;
    if (product.collection == 'v173_royal') return 1000;
    if (product.collection == 'v173_showcase') return 2000;
    return 3000;
  }

  List<StoreProduct> _visibleTables() {
    final normalizedQuery = query.trim().toLowerCase();
    final result = products.where((product) {
      if (product.category != 'tables') return false;
      if (!widget.controller.isStoreProductVisible(product)) return false;
      if (!_matchesScope(product)) return false;
      if (batch != 'all' && product.collection != batch) return false;
      if (tier != 'all' && product.tier != tier) return false;
      if (normalizedQuery.isEmpty) return true;
      final haystack = '${widget.controller.nameFor(product)} ${widget.controller.descriptionFor(product)} ${product.id}'.toLowerCase();
      return haystack.contains(normalizedQuery);
    }).toList();
    result.sort((a, b) {
      final weight = _sortWeight(a).compareTo(_sortWeight(b));
      return weight != 0 ? weight : a.id.compareTo(b.id);
    });
    return result;
  }

  StoreProduct? _featured(List<StoreProduct> visible) {
    if (visible.isEmpty) return null;
    for (final product in visible) {
      if (product.id == focusedProductId) return product;
    }
    for (final product in visible) {
      if (product.id == widget.controller.selectedTable) return product;
    }
    return visible.first;
  }

  Future<void> _buyOrActivate(StoreProduct product) async {
    final owned = widget.controller.isOwnedActiveV176(product.id);
    if (!owned) {
      await showProductPreview(context, widget.controller, product);
      if (mounted) setState(() => focusedProductId = product.id);
      return;
    }
    final ok = await widget.controller.activateProduct(product);
    if (!mounted) return;
    showToast(
      context,
      ok
          ? '${warqnaV185Text(widget.controller.localeCode, 'active')}: ${widget.controller.nameFor(product)}'
          : (widget.controller.lastStoreError ?? 'تعذر تفعيل الطاولة.'),
    );
    if (ok) setState(() => focusedProductId = product.id);
  }

  @override
  Widget build(BuildContext context) {
    final controller = widget.controller;
    final lang = controller.localeCode;
    final visible = _visibleTables();
    final featured = _featured(visible);
    final allVisibleTables = products.where((product) => product.category == 'tables' && controller.isStoreProductVisible(product)).length;

    return LayoutBuilder(builder: (context, constraints) {
      final width = constraints.maxWidth;
      final columns = width >= 1450
          ? 5
          : width >= 1120
              ? 4
              : width >= 760
                  ? 3
                  : width >= 340
                      ? 2
                      : 1;
      final cardExtent = columns >= 4 ? 258.0 : columns == 3 ? 270.0 : columns == 2 ? 278.0 : 320.0;
      final horizontalPadding = width >= 900 ? 22.0 : 12.0;

      return DecoratedBox(
        decoration: const BoxDecoration(
          gradient: RadialGradient(
            center: Alignment(0.72, -0.88),
            radius: 1.25,
            colors: <Color>[Color(0xff0b3021), warqnaV185Background, Color(0xff010705)],
          ),
        ),
        child: CustomScrollView(
          slivers: <Widget>[
            SliverPadding(
              padding: EdgeInsets.fromLTRB(horizontalPadding, 18, horizontalPadding, 0),
              sliver: SliverToBoxAdapter(
                child: _TableStoreHeaderV185(
                  controller: controller,
                  tableCount: allVisibleTables,
                  onOpenStore: () => widget.onOpenStoreCategory('all'),
                  onOpenInventory: () => widget.onOpenStoreCategory('inventory'),
                ),
              ),
            ),
            SliverPadding(
              padding: EdgeInsets.fromLTRB(horizontalPadding, 14, horizontalPadding, 0),
              sliver: SliverToBoxAdapter(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: <Widget>[
                    _StoreScopeFiltersV185(
                      language: lang,
                      selected: scope,
                      onSelected: (value) => setState(() {
                        scope = value;
                        batch = 'all';
                      }),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Expanded(
                          child: TextField(
                            onChanged: (value) => setState(() => query = value),
                            decoration: InputDecoration(
                              prefixIcon: const Icon(Icons.search_rounded),
                              hintText: warqnaV185Text(lang, 'search'),
                              filled: true,
                              fillColor: warqnaV185Panel.withValues(alpha: .9),
                            ),
                          ),
                        ),
                        const SizedBox(width: 9),
                        _TableResultCounterV185(count: visible.length, total: allVisibleTables),
                      ],
                    ),
                    if (scope == 'reference' || scope == 'all') ...<Widget>[
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 7,
                        runSpacing: 7,
                        children: <Widget>[
                          for (final entry in const <(String, String)>[
                            ('all', '1–40'),
                            ('reference_1', '1–10'),
                            ('reference_2', '11–20'),
                            ('reference_3', '21–30'),
                            ('reference_4', '31–40'),
                          ])
                            ChoiceChip(
                              selected: batch == entry.$1,
                              label: Text(entry.$2, style: const TextStyle(fontWeight: FontWeight.w900)),
                              onSelected: (_) => setState(() => batch = entry.$1),
                            ),
                        ],
                      ),
                    ],
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 7,
                      runSpacing: 7,
                      children: <Widget>[
                        for (final entry in <(String, String)>[
                          ('all', warqnaV185Text(lang, 'all')),
                          ('beginner', lang == 'ar' ? 'مبتدئ' : 'Starter'),
                          ('expert', lang == 'ar' ? 'خبير' : 'Expert'),
                          ('pro', lang == 'ar' ? 'محترف' : 'Pro'),
                          ('legendary', lang == 'ar' ? 'أسطوري' : 'Legendary'),
                        ])
                          FilterChip(
                            selected: tier == entry.$1,
                            label: Text(entry.$2, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900)),
                            onSelected: (_) => setState(() => tier = entry.$1),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            if (featured != null)
              SliverPadding(
                padding: EdgeInsets.fromLTRB(horizontalPadding, 14, horizontalPadding, 0),
                sliver: SliverToBoxAdapter(
                  child: _FeaturedTableV185(
                    controller: controller,
                    product: featured,
                    compact: width < 720,
                    onPreview: () => showProductPreview(context, controller, featured),
                    onAction: () => _buyOrActivate(featured),
                  ),
                ),
              ),
            SliverPadding(
              padding: EdgeInsets.fromLTRB(horizontalPadding, 16, horizontalPadding, 0),
              sliver: SliverToBoxAdapter(
                child: Row(
                  children: <Widget>[
                    Expanded(
                      child: Text(
                        scope == 'reference'
                            ? '${warqnaV185Text(lang, 'modern')} • ${visible.length}'
                            : '${warqnaV185Text(lang, 'tables')} • ${visible.length}',
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: warqnaV185GoldLight),
                      ),
                    ),
                    const Icon(Icons.auto_awesome_rounded, color: warqnaV185Gold, size: 20),
                  ],
                ),
              ),
            ),
            if (visible.isEmpty)
              SliverFillRemaining(
                hasScrollBody: false,
                child: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(28),
                    child: Text(warqnaV185Text(lang, 'empty'), textAlign: TextAlign.center, style: const TextStyle(color: Colors.white60)),
                  ),
                ),
              )
            else
              SliverPadding(
                padding: EdgeInsets.fromLTRB(horizontalPadding, 12, horizontalPadding, 0),
                sliver: SliverGrid(
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: columns,
                    crossAxisSpacing: 10,
                    mainAxisSpacing: 10,
                    mainAxisExtent: cardExtent,
                  ),
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final product = visible[index];
                      return _WorldTableCardV185(
                        controller: controller,
                        product: product,
                        onFocus: () => setState(() => focusedProductId = product.id),
                        onPreview: () => showProductPreview(context, controller, product),
                        onAction: () => _buyOrActivate(product),
                      );
                    },
                    childCount: visible.length,
                  ),
                ),
              ),
            SliverPadding(
              padding: EdgeInsets.fromLTRB(horizontalPadding, 16, horizontalPadding, 30),
              sliver: SliverToBoxAdapter(
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: warqnaV185Panel.withValues(alpha: .86),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: warqnaV185Gold.withValues(alpha: .22)),
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      const Icon(Icons.verified_user_rounded, color: Colors.lightGreenAccent),
                      const SizedBox(width: 10),
                      Expanded(child: Text(warqnaV185Text(lang, 'security'), style: const TextStyle(color: Colors.white70, fontSize: 11, height: 1.55))),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      );
    });
  }
}

class _TableStoreHeaderV185 extends StatelessWidget {
  final AppController controller;
  final int tableCount;
  final VoidCallback onOpenStore;
  final VoidCallback onOpenInventory;

  const _TableStoreHeaderV185({
    required this.controller,
    required this.tableCount,
    required this.onOpenStore,
    required this.onOpenInventory,
  });

  @override
  Widget build(BuildContext context) {
    final lang = controller.localeCode;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: warqnaV185Panel.withValues(alpha: .92),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: warqnaV185Gold.withValues(alpha: .28)),
        boxShadow: <BoxShadow>[BoxShadow(color: Colors.black.withValues(alpha: .38), blurRadius: 28, offset: const Offset(0, 14))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: <Widget>[
          Row(
            children: <Widget>[
              Container(
                width: 72,
                height: 72,
                padding: const EdgeInsets.all(5),
                decoration: BoxDecoration(
                  color: Colors.black.withValues(alpha: .18),
                  borderRadius: BorderRadius.circular(18),
                  border: Border.all(color: warqnaV185Gold.withValues(alpha: .22)),
                ),
                child: Image.asset('assets/images/brand/warqna_logo.png', fit: BoxFit.contain, filterQuality: FilterQuality.high),
              ),
              const SizedBox(width: 13),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Row(
                      children: <Widget>[
                        Flexible(child: Text(warqnaV185Text(lang, 'tables'), style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: warqnaV185GoldLight))),
                        const SizedBox(width: 7),
                        const Icon(Icons.table_restaurant_rounded, color: warqnaV185Gold),
                      ],
                    ),
                    const SizedBox(height: 3),
                    Text(warqnaV185Text(lang, 'subtitle'), style: const TextStyle(color: Colors.white60, fontSize: 11, height: 1.4)),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: () => showWallet(context, controller),
                borderRadius: BorderRadius.circular(16),
                child: Container(
                  constraints: const BoxConstraints(maxWidth: 132),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                  decoration: BoxDecoration(color: Colors.black26, borderRadius: BorderRadius.circular(16), border: Border.all(color: warqnaV185Gold.withValues(alpha: .25))),
                  child: Column(
                    children: <Widget>[
                      FittedBox(fit: BoxFit.scaleDown, child: Text('🪙 ${formatNumber(controller.coins)}', style: const TextStyle(color: warqnaV185GoldLight, fontWeight: FontWeight.w900, fontSize: 12))),
                      Text('$tableCount ${warqnaV185Text(lang, 'tables')}', style: const TextStyle(color: Colors.white54, fontSize: 9)),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: <Widget>[
              OutlinedButton.icon(onPressed: onOpenStore, icon: const Icon(Icons.storefront_rounded, size: 18), label: Text(warqnaV185Text(lang, 'allStore'))),
              OutlinedButton.icon(onPressed: onOpenInventory, icon: const Icon(Icons.inventory_2_outlined, size: 18), label: Text(warqnaV185Text(lang, 'inventory'))),
              _HeaderFeatureV185(icon: Icons.auto_awesome_rounded, label: warqnaV185Text(lang, 'exclusive')),
              _HeaderFeatureV185(icon: Icons.hd_rounded, label: warqnaV185Text(lang, 'quality')),
              _HeaderFeatureV185(icon: Icons.flare_rounded, label: warqnaV185Text(lang, 'effects')),
              _HeaderFeatureV185(icon: Icons.savings_outlined, label: warqnaV185Text(lang, 'prices')),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeaderFeatureV185 extends StatelessWidget {
  final IconData icon;
  final String label;
  const _HeaderFeatureV185({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 9),
        decoration: BoxDecoration(
          color: warqnaV185PanelRaised,
          borderRadius: BorderRadius.circular(13),
          border: Border.all(color: warqnaV185Gold.withValues(alpha: .16)),
        ),
        child: Row(mainAxisSize: MainAxisSize.min, children: <Widget>[
          Icon(icon, size: 17, color: warqnaV185Gold),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w900)),
        ]),
      );
}

class _StoreScopeFiltersV185 extends StatelessWidget {
  final String language;
  final String selected;
  final ValueChanged<String> onSelected;
  const _StoreScopeFiltersV185({required this.language, required this.selected, required this.onSelected});

  @override
  Widget build(BuildContext context) => Wrap(
        spacing: 8,
        runSpacing: 8,
        children: <Widget>[
          for (final entry in <(String, String)>[
            ('all', warqnaV185Text(language, 'all')),
            ('reference', warqnaV185Text(language, 'modern')),
            ('royal', warqnaV185Text(language, 'luxury')),
            ('showcase', warqnaV185Text(language, 'special')),
            ('classic', warqnaV185Text(language, 'classic')),
          ])
            ChoiceChip(
              selected: selected == entry.$1,
              avatar: selected == entry.$1 ? const Icon(Icons.check_rounded, size: 16) : null,
              label: Text(entry.$2, style: const TextStyle(fontWeight: FontWeight.w900)),
              onSelected: (_) => onSelected(entry.$1),
            ),
        ],
      );
}

class _TableResultCounterV185 extends StatelessWidget {
  final int count;
  final int total;
  const _TableResultCounterV185({required this.count, required this.total});

  @override
  Widget build(BuildContext context) => Container(
        constraints: const BoxConstraints(minHeight: 56, minWidth: 76),
        padding: const EdgeInsets.symmetric(horizontal: 10),
        decoration: BoxDecoration(color: warqnaV185Panel, borderRadius: BorderRadius.circular(16), border: Border.all(color: warqnaV185Gold.withValues(alpha: .24))),
        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: <Widget>[
          Text('$count', style: const TextStyle(color: warqnaV185GoldLight, fontWeight: FontWeight.w900, fontSize: 16)),
          Text('/ $total', style: const TextStyle(color: Colors.white54, fontSize: 9)),
        ]),
      );
}

class _FeaturedTableV185 extends StatelessWidget {
  final AppController controller;
  final StoreProduct product;
  final bool compact;
  final VoidCallback onPreview;
  final VoidCallback onAction;
  const _FeaturedTableV185({
    required this.controller,
    required this.product,
    required this.compact,
    required this.onPreview,
    required this.onAction,
  });

  Widget _image() => Hero(
        tag: 'table-market-${product.id}',
        child: AdaptiveTablePreviewV183(controller: controller, product: product),
      );

  @override
  Widget build(BuildContext context) {
    final lang = controller.localeCode;
    final owned = controller.isOwnedActiveV176(product.id);
    final active = controller.selectedTable == product.id;
    final details = Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisAlignment: MainAxisAlignment.center,
      children: <Widget>[
        Text(warqnaV185Text(lang, 'featured'), style: const TextStyle(color: warqnaV185Gold, fontWeight: FontWeight.w900, fontSize: 11)),
        const SizedBox(height: 6),
        Text(controller.nameFor(product), style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w900)),
        const SizedBox(height: 5),
        Text(controller.descriptionFor(product), maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white60, fontSize: 11, height: 1.45)),
        const SizedBox(height: 10),
        Text('🪙 ${formatNumber(controller.priceFor(product))}', style: const TextStyle(color: warqnaV185GoldLight, fontSize: 18, fontWeight: FontWeight.w900)),
        const SizedBox(height: 10),
        Row(children: <Widget>[
          Expanded(child: OutlinedButton.icon(onPressed: onPreview, icon: const Icon(Icons.visibility_outlined), label: Text(warqnaV185Text(lang, 'preview')))),
          const SizedBox(width: 8),
          Expanded(
            child: FilledButton.icon(
              onPressed: active ? null : onAction,
              icon: Icon(active ? Icons.check_circle_rounded : owned ? Icons.flash_on_rounded : Icons.shopping_bag_outlined),
              label: Text(warqnaV185Text(lang, active ? 'active' : owned ? 'activate' : 'buy')),
              style: FilledButton.styleFrom(backgroundColor: warqnaV185Gold, foregroundColor: const Color(0xff261806)),
            ),
          ),
        ]),
      ],
    );

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: <Color>[Color(0xff0b291e), Color(0xff06140f)]),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: warqnaV185Gold.withValues(alpha: .35)),
        boxShadow: <BoxShadow>[BoxShadow(color: Colors.black.withValues(alpha: .34), blurRadius: 24, offset: const Offset(0, 12))],
      ),
      child: compact
          ? Column(children: <Widget>[SizedBox(height: 190, width: double.infinity, child: _image()), const SizedBox(height: 8), details])
          : SizedBox(
              height: 270,
              child: Row(children: <Widget>[
                Expanded(flex: 6, child: _image()),
                const SizedBox(width: 18),
                Expanded(flex: 4, child: details),
              ]),
            ),
    );
  }
}

class _WorldTableCardV185 extends StatelessWidget {
  final AppController controller;
  final StoreProduct product;
  final VoidCallback onFocus;
  final VoidCallback onPreview;
  final VoidCallback onAction;
  const _WorldTableCardV185({
    required this.controller,
    required this.product,
    required this.onFocus,
    required this.onPreview,
    required this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final lang = controller.localeCode;
    final owned = controller.isOwnedActiveV176(product.id);
    final active = controller.selectedTable == product.id;
    return Semantics(
      button: true,
      selected: active,
      label: '${controller.nameFor(product)}، ${formatNumber(controller.priceFor(product))}',
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            onFocus();
            onPreview();
          },
          borderRadius: BorderRadius.circular(18),
          child: Ink(
            padding: const EdgeInsets.all(9),
            decoration: BoxDecoration(
              color: active ? const Color(0xff103424) : warqnaV185Panel.withValues(alpha: .94),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: active ? Colors.lightGreenAccent : warqnaV185Gold.withValues(alpha: .24), width: active ? 1.5 : 1),
              boxShadow: <BoxShadow>[BoxShadow(color: Colors.black.withValues(alpha: .28), blurRadius: 13, offset: const Offset(0, 8))],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: <Widget>[
                Row(children: <Widget>[
                  Expanded(child: Text(controller.nameFor(product), maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w900))),
                  if (owned) Icon(active ? Icons.check_circle_rounded : Icons.verified_rounded, color: active ? Colors.lightGreenAccent : warqnaV185Gold, size: 17),
                ]),
                const SizedBox(height: 5),
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(color: Colors.black.withValues(alpha: .22), borderRadius: BorderRadius.circular(14)),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: AdaptiveTablePreviewV183(controller: controller, product: product, compact: true),
                    ),
                  ),
                ),
                const SizedBox(height: 7),
                Row(children: <Widget>[
                  Expanded(child: Text('🪙 ${formatNumber(controller.priceFor(product))}', maxLines: 1, style: const TextStyle(color: warqnaV185GoldLight, fontWeight: FontWeight.w900, fontSize: 11))),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                    decoration: BoxDecoration(color: Colors.white.withValues(alpha: .06), borderRadius: BorderRadius.circular(9)),
                    child: Text(product.tierLabel(lang), style: const TextStyle(color: Colors.white60, fontSize: 8, fontWeight: FontWeight.w900)),
                  ),
                ]),
                const SizedBox(height: 7),
                SizedBox(
                  height: 38,
                  child: FilledButton(
                    onPressed: active ? null : onAction,
                    style: FilledButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 6),
                      backgroundColor: warqnaV185Gold,
                      foregroundColor: const Color(0xff251705),
                      disabledBackgroundColor: warqnaV185Emerald.withValues(alpha: .45),
                      disabledForegroundColor: Colors.white70,
                    ),
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(warqnaV185Text(lang, active ? 'active' : owned ? 'activate' : 'buy'), style: const TextStyle(fontWeight: FontWeight.w900)),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
