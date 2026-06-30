import 'package:flutter/material.dart';
import 'package:gifcraft/l10n/app_localizations.dart';
import 'package:gifcraft/services/purchase_service.dart';
import 'package:provider/provider.dart';

class PurchaseScreen extends StatelessWidget {
  const PurchaseScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final purchase = context.watch<PurchaseService>();

    return Scaffold(
      appBar: AppBar(title: Text(l10n.purchaseTitle)),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Icon(
            Icons.auto_awesome,
            size: 72,
            color: Theme.of(context).colorScheme.primary,
          ),
          const SizedBox(height: 16),
          Text(
            l10n.purchaseTitle,
            style: Theme.of(context).textTheme.headlineSmall,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            l10n.purchaseSubtitle,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          _FeatureRow(text: l10n.purchaseFeatureUnlimited),
          _FeatureRow(text: l10n.purchaseFeatureFormats),
          _FeatureRow(text: l10n.purchaseFeatureQuality),
          _FeatureRow(text: l10n.purchaseFeatureTemplates),
          const SizedBox(height: 24),
          if (purchase.product != null)
            Text(
              purchase.product!.price,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.headlineMedium,
            ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: purchase.isFullVersion || purchase.isLoading
                ? null
                : () => purchase.buy(),
            child: Text(
              purchase.isFullVersion ? l10n.unlocked : l10n.purchaseButton,
            ),
          ),
          const SizedBox(height: 8),
          TextButton(
            onPressed: purchase.isLoading ? null : () => purchase.restore(),
            child: Text(l10n.restorePurchase),
          ),
          if (purchase.error != null) ...[
            const SizedBox(height: 12),
            Text(
              purchase.error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
              textAlign: TextAlign.center,
            ),
          ],
        ],
      ),
    );
  }
}

class _FeatureRow extends StatelessWidget {
  const _FeatureRow({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Icon(Icons.check_circle, color: Theme.of(context).colorScheme.primary),
          const SizedBox(width: 12),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}
