(function () {
    document.documentElement.classList.add("mini-fit");
    var meta = document.querySelector('meta[name="viewport"]');
    if (meta) meta.setAttribute("content", "width=device-width, initial-scale=1, viewport-fit=cover");

    window.rookieSumar = function (juego, gano, extra) {
        localStorage.setItem("rookie-recompensa", JSON.stringify({
            id: juego + "-" + Date.now(),
            juego: juego,
            gano: !!gano,
            extra: extra || null
        }));
    };

    function ponerVolver() {
        if (!document.body || document.querySelector(".mini-volver")) return;
        var volver = document.createElement("a");
        volver.className = "mini-volver";
        volver.href = (location.pathname.indexOf("/reflejos/") !== -1 ? "../" : "") + "jugar.php";
        volver.textContent = "VOLVER";
        document.body.prepend(volver);
    }

    if (document.body) ponerVolver();
    else document.addEventListener("DOMContentLoaded", ponerVolver);
})();
