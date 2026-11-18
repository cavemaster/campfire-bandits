/**
 * Campfire Bandits - Official Website Scripts
 * - Floating Ember Particles (Canvas)
 * - Synthesized Acoustic Guitar Chords & Campfire Ambiance (Web Audio API)
 */

document.addEventListener('DOMContentLoaded', () => {
  initEmberCanvas();
  initAmbientSound();
});

/* ==========================================================================
   1. Canvas Floating Embers (Campfire Sparks)
   ========================================================================== */
function initEmberCanvas() {
  const canvas = document.getElementById('emberCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  let width = (canvas.width = window.innerWidth);
  let height = (canvas.height = window.innerHeight);

  window.addEventListener('resize', () => {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  });

  const particles = [];
  const maxParticles = 65;

  class Ember {
    constructor() {
      this.reset(true);
    }

    reset(initial = false) {
      this.x = Math.random() * width;
      this.y = initial ? Math.random() * height : height + 10;
      this.size = Math.random() * 2.5 + 0.8;
      this.speedY = Math.random() * 1.2 + 0.4;
      this.speedX = (Math.random() - 0.5) * 0.8;
      this.opacity = Math.random() * 0.7 + 0.3;
      this.hue = Math.random() * 25 + 25; // 25 (deep orange) to 50 (warm gold)
      this.decay = Math.random() * 0.003 + 0.001;
      this.sway = Math.random() * 0.02 + 0.01;
      this.swayCount = Math.random() * Math.PI * 2;
    }

    update() {
      this.y -= this.speedY;
      this.swayCount += this.sway;
      this.x += Math.sin(this.swayCount) * 0.6 + this.speedX;
      this.opacity -= this.decay;

      if (this.y < -10 || this.opacity <= 0) {
        this.reset();
      }
    }

    draw() {
      ctx.save();
      ctx.globalAlpha = Math.max(0, this.opacity);
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fillStyle = `hsl(${this.hue}, 100%, 60%)`;
      ctx.shadowColor = `hsl(${this.hue}, 100%, 50%)`;
      ctx.shadowBlur = this.size * 3;
      ctx.fill();
      ctx.restore();
    }
  }

  for (let i = 0; i < maxParticles; i++) {
    particles.push(new Ember());
  }

  function animate() {
    ctx.clearRect(0, 0, width, height);
    for (let i = 0; i < particles.length; i++) {
      particles[i].update();
      particles[i].draw();
    }
    requestAnimationFrame(animate);
  }

  animate();
}

/* ==========================================================================
   2. Interactive Ambient Sound (Acoustic Guitar Chords + Fire Crackle)
   Using Web Audio API (Zero external MP3 dependencies)
   ========================================================================== */
function initAmbientSound() {
  const toggleBtn = document.getElementById('audioToggleBtn');
  const audioIcon = document.getElementById('audioIcon');
  const audioStatus = document.getElementById('audioStatus');
  const equalizer = document.getElementById('equalizer');

  if (!toggleBtn) return;

  let audioCtx = null;
  let isPlaying = false;
  let chordInterval = null;
  let crackleNode = null;

  // Chord notes in Hz: E-Minor, G-Major, C-Major, D-Major (Classic campfire progression)
  const chords = [
    // Em: E3, G3, B3, E4
    [164.81, 196.00, 246.94, 329.63],
    // G: G3, B3, D4, G4
    [196.00, 246.94, 293.66, 392.00],
    // C: C3, E3, G3, C4
    [130.81, 164.81, 196.00, 261.63],
    // D: D3, F#3, A3, D4
    [146.83, 185.00, 220.00, 293.66]
  ];

  let currentChordIndex = 0;

  function playPluck(ctx, freq, delayTime) {
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    const filter = ctx.createBiquadFilter();

    osc.type = 'triangle';
    osc.frequency.setValueAtTime(freq, ctx.currentTime + delayTime);

    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(1400, ctx.currentTime + delayTime);
    filter.frequency.exponentialRampToValueAtTime(300, ctx.currentTime + delayTime + 1.2);

    gain.gain.setValueAtTime(0, ctx.currentTime + delayTime);
    gain.gain.linearRampToValueAtTime(0.08, ctx.currentTime + delayTime + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + delayTime + 2.0);

    osc.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);

    osc.start(ctx.currentTime + delayTime);
    osc.stop(ctx.currentTime + delayTime + 2.1);
  }

  function playCampfireCrackle(ctx) {
    const bufferSize = ctx.sampleRate * 2;
    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
    const data = buffer.getChannelData(0);

    for (let i = 0; i < bufferSize; i++) {
      if (Math.random() < 0.003) {
        data[i] = (Math.random() * 2 - 1) * 0.15;
      } else {
        data[i] = 0;
      }
    }

    const noise = ctx.createBufferSource();
    noise.buffer = buffer;
    noise.loop = true;

    const filter = ctx.createBiquadFilter();
    filter.type = 'highpass';
    filter.frequency.value = 1000;

    const gain = ctx.createGain();
    gain.gain.value = 0.05;

    noise.connect(filter);
    filter.connect(gain);
    gain.connect(ctx.destination);

    noise.start();
    return { noise, gain };
  }

  function startAudio() {
    if (!audioCtx) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    if (audioCtx.state === 'suspended') {
      audioCtx.resume();
    }

    isPlaying = true;
    audioIcon.textContent = '⏸';
    audioStatus.textContent = 'Läuft: Lagerfeuer & Akustik-Akkorde';
    equalizer.classList.add('playing');

    crackleNode = playCampfireCrackle(audioCtx);
    playChord(chords[currentChordIndex]);

    chordInterval = setInterval(() => {
      currentChordIndex = (currentChordIndex + 1) % chords.length;
      playChord(chords[currentChordIndex]);
    }, 2800);
  }

  function playChord(chord) {
    if (!audioCtx || !isPlaying) return;
    chord.forEach((freq, idx) => {
      playPluck(audioCtx, freq, idx * 0.14);
    });
  }

  function stopAudio() {
    isPlaying = false;
    audioIcon.textContent = '▶';
    audioStatus.textContent = 'Pausiert (Klicken zum Starten)';
    equalizer.classList.remove('playing');

    if (chordInterval) {
      clearInterval(chordInterval);
      chordInterval = null;
    }
    if (crackleNode) {
      try { crackleNode.noise.stop(); } catch(e) {}
      crackleNode = null;
    }
  }

  toggleBtn.addEventListener('click', () => {
    if (isPlaying) {
      stopAudio();
    } else {
      startAudio();
    }
  });
}
