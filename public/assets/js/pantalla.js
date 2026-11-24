(() => {
  'use strict';

  const contenedor = document.getElementById('pantalla-contenido');
  const avisoConexion = document.getElementById('estado-conexion');
  const botonSonido = document.getElementById('btn-sonido');
  if (!contenedor) return;

  const ENDPOINT = contenedor.dataset.endpoint || 'pantalla-estado.php';
  const INTERVALO_MS = Number(contenedor.dataset.intervalo) || 3000; // requisito: 5 s o menos
  const TIMEOUT_MS = 4000;

  // Turnos ya vistos: la campana solo suena con uno que no estaba antes,
  // así no suena cuando un turno termina y vuelve a verse uno anterior.
  const vistos = new Set(
    (contenedor.dataset.ids || '').split(',').filter(Boolean).map(Number)
  );

  let htmlActual = null;
  let temporizador = null;
  let enCurso = false;
  let sonidoActivo = false;
  let audioCtx = null;


  function mostrarConexion(enLinea) {
    if (avisoConexion) avisoConexion.classList.toggle('hidden', enLinea);
  }


  const AudioCtx = window.AudioContext || window.webkitAudioContext;
  const CLASES_ON = ['bg-indigo-600', 'text-white', 'border-indigo-600'];
  const CLASES_OFF = ['bg-white', 'text-slate-600', 'border-slate-200', 'hover:border-indigo-300'];

  function pintarBoton() {
    if (!botonSonido) return;
    botonSonido.textContent = sonidoActivo ? '🔔' : '🔕';
    botonSonido.setAttribute('aria-pressed', String(sonidoActivo));
    botonSonido.classList.remove(...(sonidoActivo ? CLASES_OFF : CLASES_ON));
    botonSonido.classList.add(...(sonidoActivo ? CLASES_ON : CLASES_OFF));
  }

  function campana() {
    if (!audioCtx) return;
    if (audioCtx.state === 'suspended') audioCtx.resume();

    const t0 = audioCtx.currentTime;
    [[880, 0], [659.25, 0.45]].forEach(([frecuencia, desfase]) => {
      const osc = audioCtx.createOscillator();
      const ganancia = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.value = frecuencia;
      ganancia.gain.setValueAtTime(0.0001, t0 + desfase);
      ganancia.gain.exponentialRampToValueAtTime(0.4, t0 + desfase + 0.02);
      ganancia.gain.exponentialRampToValueAtTime(0.0001, t0 + desfase + 1.2);
      osc.connect(ganancia).connect(audioCtx.destination);
      osc.start(t0 + desfase);
      osc.stop(t0 + desfase + 1.3);
    });
  }

  if (botonSonido) {
    if (!AudioCtx) {
      botonSonido.disabled = true;
      botonSonido.textContent = 'Sonido no disponible';
    } else {
      botonSonido.addEventListener('click', () => {
        sonidoActivo = !sonidoActivo;
        if (sonidoActivo) {
          audioCtx = audioCtx || new AudioCtx();
          campana(); 
        }
        pintarBoton();
      });
    }
  }


  function aplicar(datos) {
    if (typeof datos.html !== 'string' || !Array.isArray(datos.ids)) {
      throw new Error('Respuesta inválida');
    }

    if (datos.html !== htmlActual) {
      contenedor.innerHTML = datos.html;
      htmlActual = datos.html;
    }

    const hayTurnoNuevo = datos.actual_id !== null && !vistos.has(datos.actual_id);
    datos.ids.forEach((id) => vistos.add(id));

    if (hayTurnoNuevo && sonidoActivo) campana();
  }

  async function consultar() {
    if (enCurso) return;
    enCurso = true;

    const control = new AbortController();
    const limite = setTimeout(() => control.abort(), TIMEOUT_MS);

    try {
      const respuesta = await fetch(ENDPOINT, {
        cache: 'no-store',
        headers: { Accept: 'application/json' },
        signal: control.signal,
      });
      if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);

      aplicar(await respuesta.json());
      mostrarConexion(true);
    } catch (error) {
      mostrarConexion(false);
    } finally {
      clearTimeout(limite);
      enCurso = false;
      programar();
    }
  }

  function programar() {
    clearTimeout(temporizador);
    temporizador = setTimeout(consultar, INTERVALO_MS);
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      clearTimeout(temporizador);
      consultar();
    }
  });

  programar();
})();