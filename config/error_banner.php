<?php
/**
 * config/error_banner.php
 * -----------------------------------------------------------------
 * Maliit na JavaScript na inilalagay sa ITAAS ng bawat pahina.
 *
 * BAKIT ITO KAILANGAN:
 * Kapag may mali sa JavaScript, TAHIMIK lang ang webpage — mukhang
 * ayos ang lahat pero walang gumagana kapag pinindot. Kailangan pang
 * buksan ang DevTools (F12) para makita ang dahilan, at hindi lahat
 * alam gawin iyon.
 *
 * Ipinapakita nito ang totoong error bilang PULANG banner sa itaas ng
 * pahina, kasama kung anong file at anong linya. Kaya kahit sa ibang
 * PC o ibang browser, makikita agad kung ano ang sira.
 *
 * SADYANG LUMA ANG ISTILO NG CODE DITO (var, walang arrow function)
 * para tumakbo ito kahit sa napakalumang browser — doon pa nga ito
 * mas kailangan.
 * -----------------------------------------------------------------
 */
?>
<script>
(function () {
    var shown = false;

    /** Ipakita ang pulang banner sa itaas ng pahina. */
    function showBanner(title, detail) {
        // Isang banner lang, kahit maraming error ang sumunod.
        if (shown) { return; }
        shown = true;

        function draw() {
            var box = document.createElement("div");

            box.setAttribute("role", "alert");
            box.style.cssText =
                "position:relative;z-index:9999;margin:0;padding:14px 18px;" +
                "background:#FDECEF;color:#C62828;border-bottom:3px solid #C62828;" +
                "font-family:'Segoe UI',Tahoma,Arial,sans-serif;font-size:14px;line-height:1.6;";

            box.innerHTML =
                '<strong>' + title + '</strong><br>' +
                '<span style="font-family:Consolas,monospace;font-size:13px;">' + detail + '</span>' +
                '<br><span style="font-size:12.5px;color:#8A5A70;">' +
                'Try a hard refresh first: hold <strong>Ctrl</strong> and press <strong>F5</strong>. ' +
                'If it keeps happening, open <a href="check.php" style="color:#C62828;">check.php</a> ' +
                'and send this message along with that page.</span>';

            if (document.body) {
                document.body.insertBefore(box, document.body.firstChild);
            }
        }

        if (document.body) {
            draw();
        } else {
            document.addEventListener("DOMContentLoaded", draw);
        }
    }

    /** Gawing ligtas ang teksto bago ilagay sa HTML. */
    function safe(text) {
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
    }

    window.addEventListener("error", function (event) {
        var target = event.target;

        // ---- Hindi na-load ang isang file (script, stylesheet, larawan) ----
        if (target && target !== window && (target.src || target.href)) {
            var address = target.src || target.href;
            var tag     = (target.tagName || "").toUpperCase();

            // Ang larawan ay may sariling fallback - huwag nang ipa-alarma.
            if (tag === "IMG") { return; }

            showBanner(
                "A required file did not load.",
                safe(address) + " could not be downloaded."
            );
            return;
        }

        // ---- May mali sa loob mismo ng JavaScript ----
        var where = "";
        if (event.filename) {
            where = " (" + safe(event.filename).split("/").pop() +
                    " line " + event.lineno + ")";
        }

        showBanner(
            "The page's JavaScript stopped, so the buttons will not work.",
            safe(event.message) + where
        );
    }, true);   // true = kasama ang mga file na hindi na-load

    // ---- Nabigong fetch() o iba pang promise ----
    window.addEventListener("unhandledrejection", function (event) {
        var reason = event.reason && event.reason.message
            ? event.reason.message
            : event.reason;

        showBanner("A background request failed.", safe(reason));
    });
})();
</script>
