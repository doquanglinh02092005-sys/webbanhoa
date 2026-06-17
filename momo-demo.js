const qrTarget = document.querySelector("#momo-demo-qr");
if (qrTarget && typeof QRCode !== "undefined") {
  new QRCode(qrTarget, {
    text: qrTarget.dataset.qrPayload,
    width: 240,
    height: 240,
    colorDark: "#1f1720",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H,
  });
}
