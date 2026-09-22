<dialog id="paymentReceiptViewer" class="payment-receipt-viewer" aria-labelledby="paymentReceiptViewerTitle">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong id="paymentReceiptViewerTitle">Comprobante de pago</strong>
        <button type="button" class="btn btn-light payment-receipt-close" aria-label="Cerrar visor">&times;</button>
    </div>
    <img id="paymentReceiptViewerImage" alt="Comprobante de pago ampliado">
    <div class="text-right mt-2">
        <button type="button" class="btn btn-secondary payment-receipt-close">Cerrar</button>
    </div>
</dialog>

<style>
    .payment-receipts-section { padding: 12px; background: #fff; border: 1px solid #e1e5df; border-radius: 10px; }
    .payment-receipts-section h6 { color: #25292d; font-size: 13px; font-weight: 800; }
    .payment-receipts-upload { position: relative; display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 18px 12px; border: 1px dashed #87BC27; border-radius: 9px; background: #f9faf8; cursor: pointer; text-align: center; }
    .payment-receipts-upload i { color: #6f9e1d; }
    .payment-receipts-upload input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .payment-receipts-upload:focus-within { outline: 2px solid #87BC27; outline-offset: 2px; }
    .payment-receipts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(125px, 1fr)); gap: 10px; margin-top: 10px; }
    .payment-receipt-card { position: relative; min-width: 0; padding: 8px; border: 1px solid #e1e5df; border-radius: 9px; background: #fff; }
    .payment-receipt-open { display: flex; width: 100%; height: 90px; align-items: center; justify-content: center; border: 0; border-radius: 6px; background: #f4f5f3; color: #25292d; cursor: pointer; }
    .payment-receipt-open img { width: 100%; height: 100%; object-fit: contain; }
    .payment-receipt-name { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 6px; font-size: 12px; }
    .payment-receipt-remove { position: absolute; top: 3px; right: 3px; border: 1px solid #e1e5df; border-radius: 50%; width: 28px; height: 28px; background: #fff; color: #25292d; }
    .payment-receipt-viewer { width: min(900px, 94vw); max-height: 92vh; overflow: auto; border: 1px solid #e1e5df; border-radius: 12px; padding: 14px; color: #25292d; background: #fff; }
    .payment-receipt-viewer::backdrop { background: rgba(25, 29, 32, .8); }
    #paymentReceiptViewerImage { display: block; width: 100%; max-height: 72vh; object-fit: contain; }
    @media (pointer: coarse) { .payment-receipt-remove { width: 44px; height: 44px; } }
</style>
