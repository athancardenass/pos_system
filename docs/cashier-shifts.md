# Cashier shifts, cash movements and returns

## Open and run a shift
Open Shift records an opening float. The timer beside Close Shift measures elapsed time from that saved timestamp, including page refreshes and overnight shifts. You must close an existing shift before opening another.

Sales are recorded automatically at their final receipt value after change. Cash sales affect drawer cash; verified Card/E-wallet sales have separate totals. The register requires an open shift. Resolve pending payment verifications before closure.

## Cash movements
Use **Cash movements** in the POS header. Cash In is additional cash/panukli; Cash Out is a withdrawal/expense. Enter amount and reason. A cashier needs manager PIN approval; a signed-in Manager uses the existing audited self-approval policy.

Drawer Transfer deducts from the sending drawer and stays pending until the receiving cashier confirms physical receipt. The recipient uses the same Cash movements page. Both shifts stay open while cash is in transit. The sender can cancel an unreceived transfer with manager approval, returning its amount to the source drawer. Transfer does not erase a pre-existing shortage.

## Refunds and exchanges
Use **Find receipt** and enter the original printed receipt number (or transaction ID). Cashiers can open another cashier's receipt for an approved return, even on a later day. An active processing shift and manager approval are required. The original closed report is unchanged.

For a refund, choose item quantity, reason and condition. Resellable restores available stock and queues the current Inventory refund event. Damaged records the returned quantity but does not increase available stock. Inventory's current API does not expose a damaged-stock destination; handle its physical quarantine record in Inventory separately until that API exists.

For an exchange, select returned quantities and replacement items, then **Review price difference**. Equal price has no payment; a more expensive replacement collects the difference; a cheaper replacement refunds it. Settle through the original payment method and confirm settlement before requesting approval. Card/E-wallet differences/refunds are completed in their original merchant app or terminal; enter that settlement reference. POS records the settlement; it does not execute gateway refunds.

Replacement items use current prices without a new discount; return credit uses the original amount after discounts. The replacement receipt shows the exchange credit and actual difference. Original receipts link to replacement receipts. Returned quantities cannot be used twice. Stock failure rolls back the full exchange.

Cash refunds and cheaper-exchange payouts require enough expected cash in the processing drawer. Fund the drawer through Cash In or a confirmed transfer first if necessary; internal exchange credit is not treated as a cash payout.

Exchange credit carries the original loyalty earning basis. Equal exchanges earn no extra points; only extra spending earns more. A later replacement return reverses the correct original and/or new CRM credit through the existing durable events.

## Close and print
Close Shift displays sales, processed returns, manual movements, exchanges, transfers, and cash/card/e-wallet settlements. Count actual cash; it is not prefilled. Explain any shortage or overage. If the balance changed while counting, review and count again. Accept/cancel pending transfers and verify/reject pending payments first.

Expected cash = opening float + balance brought forward + Total Cash In - Total Cash Out.

Difference = counted money on hand - expected cash. Cash In/Out include each physical movement once; manual cash and transfers are not sales. A next-day cash refund decreases the shift that processes it. Sales before returns reflect final receipt values after original discounts; net sales subtracts returns processed in this shift.

After confirmation, **Last shift report** opens the saved printable report. Managers can also use Cash Drawers → View / print. Cashiers can access their own shift reports. Closed snapshots remain fixed; no success browser alert appears. Pre-feature shifts disclose brought-forward balances/limited history rather than inventing old movements.
