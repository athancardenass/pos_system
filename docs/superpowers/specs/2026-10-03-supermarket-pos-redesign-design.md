# Supermarket POS Redesign

## Goal

Rebuild the POS presentation as a complete cashier workstation inspired by the supplied reference image. Preserve existing routes, authorization, checkout calculations, inventory behavior, refund behavior, and database structure. Keep the existing peach, teal, coral, and green palette as the foundation, with a compact, professional retail interface.

## Experience and layout

- Put the global navigation in a compact top bar. Keep Dashboard near the beginning and Reports at the end. Preserve role-based access and links to all existing management functions. Promotions and Coupons are out of scope and must not appear in the redesigned navigation.
- Give the POS a viewport-filling desktop layout: product search and independently scrollable product grid on the left; a live transaction with independently scrollable item rows in the center; customer, loyalty, discount, payment, and sale actions on the right.
- Keep the live transaction summary and completion actions visible without page-level scrolling at common desktop cashier resolutions. Use a responsive fallback for narrower screens that maintains reachable controls and scrolls only content panels where practical.
- Search customers in a text input by customer ID, loyalty card ID, or name. Walk-in remains selected by default. Avoid rendering a customer dropdown containing the full customer list. If current view data does not provide a card identifier or scalable lookup, add only the minimal read/query support needed; do not change the customer schema.
- Retain catalog categories, barcode/product search, stock visibility, quantity changes, existing discount selection, payment methods, payment references, cash received/change, sale completion, keyboard shortcuts, drawer interactions, and new ticket behavior.

## Live transaction adjustments

Show any applied existing discount directly in the live transaction, with its readable name/identifier/details and negative applied amount. Show subtotal, discount savings, applicable VAT/tax information when it can be determined without changing checkout behavior, and final total. Use the existing discount data and calculations only; do not add promotion or coupon UI, calculations, or backend support. Provide usable pending discount feedback when a discount is being selected but has not yet been validated.

## Receipt

Redesign the on-screen and printed receipt with a compact thermal-receipt hierarchy: store name and information; receipt/transaction number; date/time; cashier; customer and loyalty identifiers when available; item description, quantity, unit price, and line total; applicable existing discount name/identifier/details and amount; subtotal; existing VAT/tax figures; total; payment method, amount received, change, and payment reference when available; and the existing return/footer information. Do not show promotions or coupons. Preserve refund and print actions and existing receipt data semantics. Do not invent store registration details that are not configured; use existing values or clearly editable/configurable defaults only if already supported.

## Implementation boundaries

- Likely presentation scope: `resources/views/layouts/app.blade.php`, POS views under `resources/views/pos/`, and management views that need to adopt the shared navigation/layout presentation.
- Adjust `config/roles.php` only if required to reorder navigation; preserve its existing module access. Promotions and Coupons management are excluded from this redesign. The project normally prohibits config edits unless expressly in scope, so prefer ordering in the layout if it can satisfy the request without changing permissions.
- Prefer a dedicated POS stylesheet or scoped styles over changing checkout services. Keep backend edits limited to customer lookup data if the existing IDs/card data cannot support the requested search.
- Do not modify schema, routes, composer/package manifests, or environment configuration unless a concrete requirement proves it necessary. Do not edit address fields.
- Append implementation file list, date, and reason to `CHANGELOG.md`. Do not perform Git write commands.

## Acceptance checks

- At the target desktop viewport, the complete three-column workstation and primary controls are visible without page-level scrolling; products and long cart contents scroll within their own regions.
- Search by customer ID, loyalty card ID, and name selects one registered customer; the default remains Walk-in.
- Applying an existing discount visibly updates the live transaction with its relevant identifier/details and calculated savings; the completed receipt shows the same identifier/details and amount. Promotions and Coupons are absent from the redesigned POS, navigation, live transaction, and receipt.
- All existing payment, checkout, refund, print, inventory, and role-gated navigation behavior remains available.
- Run the project-required PHP/view/route checks after implementation, and report any environment-dependent checks that cannot run.

## Open implementation note

The supplied reference shows navigation across the top and a three-column POS. It does not specify a mobile cashier workflow or provide authoritative store address/VAT registration values. Retain the existing receipt values/data sources and prioritize desktop cashier use; do not add unsupported store claims. Promotions and Coupons are unavailable in the CRM database/schema and are explicitly excluded. Keep Discount only where supported by the existing database and backend. Do not create Promotion or Coupon tables, fields, or backend structures.
