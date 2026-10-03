<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>พิมพ์งาน | PrintShop</title>
    <style>
        html,body{margin:0;width:100%;height:100%;background:#fff;font-family:Arial,"Noto Sans Thai",sans-serif}
        #pdfFrame{position:fixed;inset:0;width:100%;height:100%;border:0;background:#fff}
        #fallback{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#fff;z-index:2;color:#163b60}
        #fallbackBox{text-align:center;padding:24px}
        #fallback a{display:inline-block;margin-top:14px;padding:10px 16px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-weight:700}
    </style>
</head>
<body>
    <div id="fallback">
        <div id="fallbackBox">
            <div>กำลังเปิดหน้าต่างพิมพ์...</div>
            <a href="{{ $pdfUrl }}" target="_self">เปิดไฟล์ PDF</a>
        </div>
    </div>

    <iframe id="pdfFrame" src="{{ $pdfUrl }}#toolbar=0&navpanes=0"></iframe>

    <script>
        const frame = document.getElementById('pdfFrame');
        const fallback = document.getElementById('fallback');
        let printTried = false;

        function openPrintDialog() {
            if (printTried) return;
            printTried = true;

            setTimeout(() => {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    fallback.style.display = 'none';
                } catch (e) {
                    // บางเวอร์ชันของเบราว์เซอร์ไม่อนุญาตให้สั่ง print PDF จาก iframe โดยตรง
                    // กรณีนั้นให้เปิด PDF ต้นฉบับแทนโดยอัตโนมัติ
                    window.location.replace(@json($pdfUrl));
                }
            }, 700);
        }

        frame.addEventListener('load', openPrintDialog);

        // สำรองกรณี PDF viewer ไม่ยิง load event ตามปกติ
        setTimeout(openPrintDialog, 1800);
    </script>
</body>
</html>
