(function () {
    document.documentElement.classList.add("mini-fit");
    var meta = document.querySelector('meta[name="viewport"]');
    if (meta) meta.setAttribute("content", "width=device-width, initial-scale=1, viewport-fit=cover");

    var enCurso = false;

    window.rookieSumar = function (juego, gano, extra) {
        localStorage.setItem("rookie-recompensa", JSON.stringify({
            id: juego + "-" + Date.now(),
            juego: juego,
            gano: !!gano,
            extra: extra || null
        }));
    };

    window.rookieMarcar = function (activo) {
        enCurso = !!activo;
    };

    function modoPartido() {
        return new URLSearchParams(location.search).get("modo") === "partido";
    }

    function destino() {
        return (location.pathname.indexOf("/reflejos/") !== -1 ? "../" : "") + "jugar.php";
    }

    function irAlLobby() {
        enCurso = false;
        location.href = destino();
    }

    function preguntar() {
        var capa = document.querySelector(".mini-salir");
        if (!capa) return;
        var partido = modoPartido();
        capa.querySelector("p").textContent = partido
            ? "¿Abandonás el partido?"
            : "¿Abandonás el entrenamiento?";
        capa.querySelector(".no").textContent = partido
            ? "No, seguir jugando"
            : "No, seguir entrenando";
        capa.hidden = false;
    }

    function ponerSalir() {
        if (!document.body || document.querySelector(".mini-volver")) return;
        var salir = document.createElement("button");
        salir.type = "button";
        salir.className = "mini-volver";
        salir.textContent = "SALIR";
        salir.addEventListener("click", function () {
            if (enCurso) preguntar();
            else irAlLobby();
        });

        var capa = document.createElement("div");
        capa.className = "mini-salir";
        capa.hidden = true;
        capa.innerHTML = '<div class="mini-salir-panel" role="dialog" aria-modal="true">'
            + '<p>¿Abandonás el entrenamiento?</p>'
            + '<button type="button" class="si">Sí</button>'
            + '<button type="button" class="no">No, seguir entrenando</button>'
            + '</div>';
        capa.querySelector(".si").addEventListener("click", irAlLobby);
        capa.querySelector(".no").addEventListener("click", function () {
            capa.hidden = true;
        });
        capa.addEventListener("click", function (evento) {
            if (evento.target === capa) capa.hidden = true;
        });

        document.body.prepend(salir);
        document.body.appendChild(capa);
    }

    if (document.body) ponerSalir();
    else document.addEventListener("DOMContentLoaded", ponerSalir);
})();
