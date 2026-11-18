/* 3D intro: a warm cartoon desk. Scroll orbits the camera around the desk, then pushes into the monitor,
   whose screen shows the site itself, so the page below continues where the screen ends.
   Needs THREE (r128) loaded first. Exposes window.Desk3D = { progress(p), redraw() }. */
(function () {
  'use strict';
  var canvas = document.getElementById('desk-canvas');
  if (!canvas || !window.THREE || !window.WebGLRenderingContext) { document.documentElement.classList.add('no3d'); return; }
  var T = THREE, root = document.documentElement;
  var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- renderer / scene ---------- */
  var renderer;
  // phones and small screens get a lighter renderer: lower resolution, no MSAA, cheaper shadows, fewer idle frames
  var lite = matchMedia('(pointer: coarse), (max-width: 900px)').matches;
  try { renderer = new T.WebGLRenderer({ canvas: canvas, antialias: !lite, powerPreference: 'high-performance' }); }
  catch (e) { root.classList.add('no3d'); return; }
  renderer.setPixelRatio(Math.min(devicePixelRatio || 1, lite ? 1.25 : 1.5));
  renderer.outputEncoding = T.sRGBEncoding;
  renderer.toneMapping = T.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;
  renderer.shadowMap.enabled = true;
  renderer.shadowMap.type = lite ? T.PCFShadowMap : T.PCFSoftShadowMap;

  var scene = new T.Scene();
  scene.fog = new T.Fog(0x120c2a, 40, 90);
  var camera = new T.PerspectiveCamera(40, 1, 0.1, 200);

  /* ---------- helpers ---------- */
  function std(color, rough, metal, extra) {
    var m = new T.MeshStandardMaterial({ color: color, roughness: rough == null ? .6 : rough, metalness: metal || 0 });
    for (var k in extra) m[k] = extra[k];
    return m;
  }
  function mesh(geo, mat, x, y, z, parent) {
    var m = new T.Mesh(geo, mat); m.position.set(x || 0, y || 0, z || 0);
    m.castShadow = true; m.receiveShadow = true; (parent || scene).add(m); return m;
  }
  // a box with soft rounded edges: extruded rounded rectangle, centred
  function rbox(w, h, d, r, seg) {
    seg = seg || 4;
    r = Math.min(r, w / 2 - .001, h / 2 - .001, d / 2 - .001);
    var s = new T.Shape(), x = -w / 2 + r, y = -h / 2 + r, iw = w - 2 * r, ih = h - 2 * r;
    s.moveTo(x, y - r); s.lineTo(x + iw, y - r); s.quadraticCurveTo(x + iw + r, y - r, x + iw + r, y);
    s.lineTo(x + iw + r, y + ih); s.quadraticCurveTo(x + iw + r, y + ih + r, x + iw, y + ih + r);
    s.lineTo(x, y + ih + r); s.quadraticCurveTo(x - r, y + ih + r, x - r, y + ih);
    s.lineTo(x - r, y); s.quadraticCurveTo(x - r, y - r, x, y - r);
    var g = new T.ExtrudeGeometry(s, { depth: d - 2 * r, bevelEnabled: true, bevelThickness: r, bevelSize: r * .98, bevelSegments: seg, curveSegments: seg + 2 });
    g.translate(0, 0, -(d - 2 * r) / 2); g.computeVertexNormals(); return g;
  }
  function canvasTex(w, h, paint) {
    var c = document.createElement('canvas'); c.width = w; c.height = h;
    paint(c.getContext('2d'), w, h);
    var t = new T.CanvasTexture(c); t.encoding = T.sRGBEncoding; t.anisotropy = renderer.capabilities.getMaxAnisotropy();
    t.cv = c; return t;
  }

  /* ---------- room ---------- */
  var floorMat = std(0x3a2a4a, .9);
  var floor = mesh(new T.PlaneGeometry(200, 200), floorMat, 0, -11, 0); floor.rotation.x = -Math.PI / 2; floor.castShadow = false;
  var wallMat = std(0x2b2150, .95);
  var wall = mesh(new T.PlaneGeometry(200, 80), wallMat, 0, 20, -9); wall.castShadow = false;

  // window on the back wall: night sky with moon, or a day sky
  var skyTex = canvasTex(512, 384, function () {});
  // Tehran skyline: Milad Tower plus blocks of flats whose windows flicker on and off
  var city = [], cseed = 11;
  function crand() { cseed = (cseed * 9301 + 49297) % 233280; return cseed / 233280; }
  (function () {
    var x = 0;
    while (x < 512) {
      var bw = 34 + crand() * 50, top = 250 + crand() * 80, wins = [];
      for (var wy = top + 10; wy < 372; wy += 16) for (var wx = x + 6; wx < x + bw - 8; wx += 12) wins.push({ x: wx, y: wy, on: crand() < .55, f: crand() < .12 });
      city.push({ x: x, w: bw, top: top, wins: wins }); x += bw + 2 + crand() * 6;
    }
  })();
  var stars = []; for (var si0 = 0; si0 < 90; si0++) stars.push([crand() * 512, crand() * 230, .3 + crand() * .7]);
  function miladTower(g, cx, base, col, lights, blink) {
    g.fillStyle = col;
    g.beginPath(); g.moveTo(cx - 26, base); g.lineTo(cx - 9, base - 40); g.lineTo(cx + 9, base - 40); g.lineTo(cx + 26, base); g.fill();     // flared foot
    g.beginPath(); g.moveTo(cx - 9, base - 40); g.lineTo(cx - 5, 150); g.lineTo(cx + 5, 150); g.lineTo(cx + 9, base - 40); g.fill();         // shaft
    g.beginPath(); g.moveTo(cx - 12, 152); g.lineTo(cx - 30, 138); g.lineTo(cx - 32, 118); g.lineTo(cx - 24, 104); g.lineTo(cx + 24, 104);  // the pod
    g.lineTo(cx + 32, 118); g.lineTo(cx + 30, 138); g.lineTo(cx + 12, 152); g.fill();
    g.fillRect(cx - 4, 70, 8, 36);                                                                                                         // upper shaft
    g.beginPath(); g.moveTo(cx - 3, 72); g.lineTo(cx, 18); g.lineTo(cx + 3, 72); g.fill();                                                    // mast
    if (lights) {
      g.fillStyle = '#ffd36b'; for (var k = -26; k <= 26; k += 6) g.fillRect(cx + k - 1, 120, 3, 4);
      g.fillStyle = '#9fd8ff'; for (var k2 = -20; k2 <= 20; k2 += 6) g.fillRect(cx + k2 - 1, 132, 3, 3);
      g.fillStyle = blink ? '#ff3b3b' : '#6a1520'; g.beginPath(); g.arc(cx, 20, 3.5, 0, 7); g.fill();
    }
  }
  var skyT = 0;
  function paintSky() {
    var c = skyTex.cv, g = c.getContext('2d'), w = c.width, h = c.height, day = root.dataset.theme === 'day';
    var grd = g.createLinearGradient(0, 0, 0, h);
    if (day) { grd.addColorStop(0, '#7cc4ff'); grd.addColorStop(1, '#e6f4ff'); } else { grd.addColorStop(0, '#0b0b33'); grd.addColorStop(1, '#3a2a78'); }
    g.fillStyle = grd; g.fillRect(0, 0, w, h);
    if (day) {
      g.fillStyle = '#fff6c4'; g.beginPath(); g.arc(420, 70, 36, 0, 7); g.fill();
      g.fillStyle = 'rgba(255,255,255,.9)';
      [[300, 110, .9], [430, 180, .7]].forEach(function (c) { var x = c[0], y = c[1], s = c[2]; g.beginPath(); g.arc(x, y, 26 * s, 0, 7); g.arc(x + 30 * s, y - 12 * s, 32 * s, 0, 7); g.arc(x + 62 * s, y, 24 * s, 0, 7); g.fill(); });
      g.fillStyle = 'rgba(160,175,200,.7)'; g.beginPath(); g.moveTo(0, 270); g.lineTo(90, 200); g.lineTo(170, 245); g.lineTo(260, 190); g.lineTo(380, 240); g.lineTo(512, 205); g.lineTo(512, 300); g.lineTo(0, 300); g.fill();   // Alborz
    } else {
      stars.forEach(function (st) { g.fillStyle = 'rgba(255,255,255,' + st[2] + ')'; g.fillRect(st[0], st[1], 2, 2); });
      g.fillStyle = '#fff8e0'; g.beginPath(); g.arc(420, 80, 32, 0, 7); g.fill();
      g.fillStyle = '#d9d2ee'; g.beginPath(); g.arc(410, 72, 7, 0, 7); g.arc(432, 92, 5, 0, 7); g.fill();
      g.fillStyle = 'rgba(30,22,80,.8)'; g.beginPath(); g.moveTo(0, 270); g.lineTo(90, 205); g.lineTo(170, 248); g.lineTo(260, 195); g.lineTo(380, 242); g.lineTo(512, 210); g.lineTo(512, 300); g.lineTo(0, 300); g.fill();
    }
    miladTower(g, 150, 384, day ? '#8c97ad' : '#2c2166', !day, (skyT % 1.6) < .8);
    city.forEach(function (b) {
      g.fillStyle = day ? '#b8c2d4' : '#120a30'; g.fillRect(b.x, b.top, b.w, h - b.top);
      if (day) return;
      b.wins.forEach(function (wi) {
        if (wi.f && Math.random() < .25) wi.on = !wi.on;                       // some lights flicker
        else if (Math.random() < .004) wi.on = !wi.on;                         // others switch now and then
        if (wi.on) { g.fillStyle = wi.f ? '#ffe9a8' : '#ffd36b'; g.fillRect(wi.x, wi.y, 6, 8); }
      });
    });
    skyTex.needsUpdate = true;
  }
  var win = new T.Group(); win.position.set(-11, 9, -8.9); scene.add(win);
  var glass = new T.Mesh(new T.PlaneGeometry(10, 7.5), new T.MeshBasicMaterial({ map: skyTex, toneMapped: false })); win.add(glass);
  var frameMat = std(0xf3e4c8, .7);
  [[0, 3.9, 10.8, .5], [0, -3.9, 10.8, .5], [-5.15, 0, .5, 8.3], [5.15, 0, .5, 8.3], [0, 0, .25, 7.5], [0, 0, 10, .25]].forEach(function (f) { mesh(new T.BoxGeometry(f[2], f[3], .4), frameMat, f[0], f[1], .1, win); });
  mesh(new T.BoxGeometry(11.6, .4, 1.4), frameMat, 0, -4.25, .5, win);

  // a framed poster on the wall
  var posterTex = canvasTex(256, 320, function (g, w, h) {
    g.fillStyle = '#ffc145'; g.fillRect(0, 0, w, h);
    g.fillStyle = '#1a1130'; g.font = '900 120px Poppins, sans-serif'; g.textAlign = 'center'; g.fillText('MJ', w / 2, 190);
    g.font = '700 26px monospace'; g.fillText('{ code }', w / 2, 250);
  });
  var poster = new T.Group(); poster.position.set(12, 10, -8.85); scene.add(poster);
  mesh(new T.BoxGeometry(5.4, 6.6, .3), std(0x1a1130, .5), 0, 0, 0, poster);
  var pimg = new T.Mesh(new T.PlaneGeometry(4.8, 6), new T.MeshStandardMaterial({ map: posterTex, roughness: .8 })); pimg.position.z = .16; poster.add(pimg);

  /* ---------- desk ---------- */
  var woodTex = canvasTex(1024, 512, function (g, w, h) {
    g.fillStyle = '#b77a45'; g.fillRect(0, 0, w, h);
    for (var i = 0; i < 160; i++) {
      var y = Math.random() * h, a = .04 + Math.random() * .1;
      g.strokeStyle = Math.random() < .5 ? 'rgba(90,45,15,' + a + ')' : 'rgba(255,210,150,' + a + ')';
      g.lineWidth = 1 + Math.random() * 3; g.beginPath(); g.moveTo(0, y);
      for (var x = 0; x <= w; x += 32) g.lineTo(x, y + Math.sin(x / 120 + i) * 6 + Math.sin(x / 37) * 2);
      g.stroke();
    }
  });
  woodTex.wrapS = woodTex.wrapT = T.RepeatWrapping;
  var wood = std(0xffffff, .55, 0, { map: woodTex });
  var top = mesh(rbox(20, .8, 10, .3), wood, 0, -.4, 0);
  var legMat = std(0x2a2340, .4, .3);
  [[-9, -4], [9, -4], [-9, 4], [9, 4]].forEach(function (l) { mesh(rbox(.7, 10.2, .7, .2), legMat, l[0], -5.9, l[1]); });
  mesh(rbox(18.6, 1.6, .4, .15), legMat, 0, -1.6, -4.3);

  /* ---------- monitor ---------- */
  var SCREEN_W = 9, SCREEN_H = 5.2, SCREEN_Y = 5.4, SCREEN_Z = -1.75;
  var monitor = new T.Group(); scene.add(monitor);
  var bezelMat = std(0x1d1733, .35, .2);
  mesh(rbox(SCREEN_W + .7, SCREEN_H + .9, .5, .2), bezelMat, 0, SCREEN_Y - .1, SCREEN_Z - .27, monitor);
  mesh(rbox(1, 3.6, .45, .15), std(0xc9c3d8, .3, .6), 0, 1.8, SCREEN_Z - .75, monitor);
  var base = mesh(new T.CylinderGeometry(1.8, 2, .25, 48), std(0xc9c3d8, .3, .6), 0, .12, SCREEN_Z - .5, monitor); base.scale.z = .7;
  var logo = mesh(new T.CircleGeometry(.12, 24), new T.MeshBasicMaterial({ color: 0xffc145 }), 0, SCREEN_Y - SCREEN_H / 2 - .3, SCREEN_Z + .001, monitor); logo.castShadow = false;

  // the screen shows a miniature of this site; redrawn when language or theme changes
  var screenTex = canvasTex(1600, 924, function () {});
  var screen = new T.Mesh(new T.PlaneGeometry(SCREEN_W, SCREEN_H), new T.MeshBasicMaterial({ map: screenTex, toneMapped: false }));
  screen.position.set(0, SCREEN_Y, SCREEN_Z + .005); monitor.add(screen);
  // fades the miniature out to the page colour just before the real page takes over (no double image)
  var cover = new T.Mesh(new T.PlaneGeometry(SCREEN_W, SCREEN_H), new T.MeshBasicMaterial({ color: 0x0a0720, transparent: true, opacity: 0, toneMapped: false }));
  cover.position.set(0, SCREEN_Y, SCREEN_Z + .012); monitor.add(cover);
  function roundRect(g, x, y, w, h, r) { g.beginPath(); g.moveTo(x + r, y); g.arcTo(x + w, y, x + w, y + h, r); g.arcTo(x + w, y + h, x, y + h, r); g.arcTo(x, y + h, x, y, r); g.arcTo(x, y, x + w, y, r); g.closePath(); }
  function paintScreen() {
    var c = screenTex.cv, g = c.getContext('2d'), w = c.width, h = c.height;
    var cs = getComputedStyle(root), v = function (n) { return cs.getPropertyValue(n).trim(); };
    var I = window.I18N, lang = root.lang in (I ? I.t : {}) ? root.lang : 'fa', t = I ? I.t[lang] : {}, rtl = root.dir === 'rtl';
    var font = v('--font') || 'Vazir, sans-serif';
    g.fillStyle = v('--bg'); g.fillRect(0, 0, w, h);
    g.direction = rtl ? 'rtl' : 'ltr'; g.textAlign = rtl ? 'right' : 'left';
    var X = rtl ? w - 110 : 110, sx = rtl ? -1 : 1;
    // headline (same words as the section under the film)
    g.fillStyle = v('--muted'); g.font = '500 30px ' + font; g.fillText((t['hello.avail'] || ''), X, 250);
    g.fillStyle = v('--ink'); g.font = '900 118px ' + font;
    g.fillText(String(t['hello.h1'] || '').replace(/<[^>]+>/g, ''), X, 400);
    g.fillStyle = v('--muted'); g.font = '500 36px ' + font; g.fillText(t['intro.role'] || '', X, 480);
    // buttons
    var bx = rtl ? w - 110 - 330 : 110;
    g.fillStyle = v('--shadow'); roundRect(g, bx + 8, 548, 330, 86, 20); g.fill();
    g.fillStyle = v('--accent'); roundRect(g, bx, 540, 330, 86, 20); g.fill();
    g.fillStyle = v('--on-accent'); g.font = '900 32px ' + font; g.textAlign = 'center'; g.fillText(t['hello.btn1'] || '', bx + 165, 595);
    // stat cards
    var cards = [[t.n17, v('--accent')], [t.n30, v('--accent3')], [t.n1000, v('--accent2')]];
    cards.forEach(function (cd, i) {
      var cw = 300, cx = rtl ? w - 110 - cw - i * 330 : 110 + i * 330;
      g.fillStyle = v('--shadow'); roundRect(g, cx + 10, 730, cw, 140, 24); g.fill();
      g.fillStyle = v('--paper'); roundRect(g, cx, 720, cw, 140, 24); g.fill();
      g.fillStyle = cd[1]; g.font = '900 64px ' + font; g.fillText(cd[0] || '', cx + cw / 2, 812);
    });
    g.textAlign = 'left';
    screenTex.needsUpdate = true;
  }

  // sticky note on the bezel
  var noteTex = canvasTex(256, 256, function (g) {
    g.fillStyle = '#ffe36e'; g.fillRect(0, 0, 256, 256);
    g.fillStyle = '#5a4300'; g.font = '700 54px "Comic Sans MS", cursive'; g.textAlign = 'center';
    g.fillText('SEO ✓', 128, 110); g.font = '700 40px "Comic Sans MS", cursive'; g.fillText('1000+', 128, 180);
  });
  var note = new T.Mesh(new T.PlaneGeometry(1.3, 1.3), new T.MeshStandardMaterial({ map: noteTex, roughness: .9 }));
  note.position.set(SCREEN_W / 2 + .9, SCREEN_Y + 1.7, SCREEN_Z + .02); note.rotation.z = -.18; monitor.add(note);

  /* ---------- keyboard + mouse ---------- */
  var kb = new T.Group(); kb.position.set(-.6, 0, 2.2); kb.rotation.y = .04; scene.add(kb);
  mesh(rbox(7.6, .35, 2.7, .12), std(0x2a2346, .45, .1), 0, .17, 0, kb);
  var keyGeo = rbox(.42, .22, .42, .07), keyMat = std(0xf3ecdf, .5), keyAcc = std(0xffc145, .45), keyRed = std(0xff5d73, .45);
  for (var r = 0; r < 4; r++) for (var c = 0; c < 14; c++) {
    var accent = (r === 3 && c === 13) || (r === 0 && c === 0) ? keyAcc : (r === 2 && c === 12 ? keyRed : keyMat);
    mesh(keyGeo, accent, -3.3 + c * .507, .45, -.95 + r * .56, kb);
  }
  var space = mesh(rbox(3, .22, .42, .07), keyMat, 0, .45, 1.29, kb);
  var pad = mesh(rbox(3.4, .06, 3, .3), std(0x3b2f63, .95), 5.2, .03, 2.4); pad.castShadow = false;
  var mouse = mesh(new T.SphereGeometry(.5, 32, 24), std(0xf3ecdf, .4), 5.2, .25, 2.5); mouse.scale.set(1, .55, 1.45);

  /* ---------- coffee mug with steam ---------- */
  var mug = new T.Group(); mug.position.set(-7, 0, 1.2); scene.add(mug);
  var prof = [];
  for (var i = 0; i <= 12; i++) { var yy = i / 12 * 2.2; prof.push(new T.Vector2(.95 + Math.sin(i / 12 * Math.PI) * .06, yy)); }
  prof.unshift(new T.Vector2(0, 0));
  mesh(new T.LatheGeometry(prof, 48), std(0xff5d73, .35), 0, 0, 0, mug).material.side = T.DoubleSide;
  var coffee = mesh(new T.CircleGeometry(.92, 40), std(0x3b1d0e, .2), 0, 1.95, 0, mug); coffee.rotation.x = -Math.PI / 2;
  var handle = mesh(new T.TorusGeometry(.55, .14, 16, 32, Math.PI * 1.2), std(0xff5d73, .35), -1.02, 1.1, 0, mug); handle.rotation.z = Math.PI * .4;
  var steamTex = canvasTex(64, 64, function (g) { var gr = g.createRadialGradient(32, 32, 0, 32, 32, 32); gr.addColorStop(0, 'rgba(255,255,255,.55)'); gr.addColorStop(1, 'rgba(255,255,255,0)'); g.fillStyle = gr; g.fillRect(0, 0, 64, 64); });
  var steam = [];
  for (var s = 0; s < 14; s++) {
    var sp = new T.Sprite(new T.SpriteMaterial({ map: steamTex, transparent: true, depthWrite: false, opacity: 0 }));
    sp.userData.off = s / 14; mug.add(sp); steam.push(sp);
  }

  /* ---------- plant ---------- */
  var plant = new T.Group(); plant.position.set(7.2, 0, -2); scene.add(plant);
  var potProf = [new T.Vector2(0, 0), new T.Vector2(.9, 0), new T.Vector2(1.2, 2), new T.Vector2(1.35, 2.05), new T.Vector2(1.35, 2.4), new T.Vector2(1.15, 2.4)];
  mesh(new T.LatheGeometry(potProf, 48), std(0xd9693b, .7), 0, 0, 0, plant);
  var soil = mesh(new T.CircleGeometry(1.15, 32), std(0x3a2414, 1), 0, 2.25, 0, plant); soil.rotation.x = -Math.PI / 2;
  var leafShape = new T.Shape(); leafShape.moveTo(0, 0); leafShape.quadraticCurveTo(.7, 1.2, 0, 3); leafShape.quadraticCurveTo(-.7, 1.2, 0, 0);
  var leafGeo = new T.ExtrudeGeometry(leafShape, { depth: .04, bevelEnabled: true, bevelThickness: .03, bevelSize: .03, bevelSegments: 2 });
  var leaves = [];
  for (var l = 0; l < 11; l++) {
    var pivot = new T.Group(); pivot.position.y = 2.2; pivot.rotation.y = l / 11 * Math.PI * 2 + (l % 2) * .3; plant.add(pivot);
    var leaf = mesh(leafGeo, std(l % 3 ? 0x3faa5b : 0x2e8a48, .55), 0, 0, 0, pivot);
    leaf.rotation.x = -.25 - (l % 3) * .22; leaf.scale.setScalar(.8 + (l % 4) * .12);
    leaves.push({ p: pivot, l: leaf, base: leaf.rotation.x, k: l });
  }

  /* ---------- desk lamp (the warm key light), set back at the left so it never blocks the screen ---------- */
  var lampMat = std(0x4ad6c0, .35, .3);
  var LA = new T.Vector3(-8.4, .3, -3), LB = new T.Vector3(-7.7, 4.8, -2.5), LC = new T.Vector3(-6.3, 6.3, -1.2), LT = new T.Vector3(-3.2, 0, 1.4);
  function rod(a, b) {
    var len = a.distanceTo(b), m = mesh(new T.CylinderGeometry(.13, .13, len, 14), lampMat, (a.x + b.x) / 2, (a.y + b.y) / 2, (a.z + b.z) / 2);
    m.quaternion.setFromUnitVectors(new T.Vector3(0, 1, 0), b.clone().sub(a).normalize());
  }
  mesh(new T.CylinderGeometry(1, 1.15, .3, 40), lampMat, LA.x, .15, LA.z);
  rod(LA, LB); rod(LB, LC);
  [LB, LC].forEach(function (j) { mesh(new T.SphereGeometry(.24, 16, 12), std(0x1d1733, .4, .4), j.x, j.y, j.z); });
  var head = new T.Group(); head.position.copy(LC); scene.add(head); head.lookAt(LT);
  var shade = mesh(new T.ConeGeometry(1, 1.5, 40, 1, true), std(0x4ad6c0, .35, .3, { side: T.DoubleSide }), 0, 0, .35, head); shade.rotation.x = -Math.PI / 2;
  var bulb = mesh(new T.SphereGeometry(.36, 24, 16), new T.MeshBasicMaterial({ color: 0xfff1c9 }), 0, 0, .8, head); bulb.castShadow = false;

  /* ---------- books + pencil cup ---------- */
  var books = new T.Group(); books.position.set(-5.4, 0, -.2); books.rotation.y = .5; scene.add(books);
  [[0x5b7cfa, .55], [0xffc145, .5], [0xff5d73, .6]].forEach(function (b, i) { var bk = mesh(rbox(2.4 - i * .15, b[1], 1.7, .06), std(b[0], .7), 0, i * .56 + b[1] / 2, 0, books); bk.rotation.y = (i - 1) * .12; });
  var cup = mesh(new T.CylinderGeometry(.5, .45, 1.4, 32, 1, true), std(0x2a2346, .5, 0, { side: T.DoubleSide }), 3.8, .7, -3.2);
  [[0xffc145, .2], [0xff5d73, -.15], [0x4ad6c0, .05]].forEach(function (p, i) { var pc = mesh(new T.CylinderGeometry(.06, .06, 2, 8), std(p[0], .5), 3.8 + (i - 1) * .15, 1.6, -3.2 + (i - 1) * .1); pc.rotation.z = p[1]; });

  /* ---------- furnishing: the rest of a lived-in room ---------- */
  // left wall + skirting, so the desk sits in a corner
  var lwall = mesh(new T.PlaneGeometry(120, 80), wallMat, -24, 20, 40); lwall.rotation.y = Math.PI / 2; lwall.castShadow = false;
  var skirtMat = std(0xf3e4c8, .6);
  mesh(new T.BoxGeometry(120, .9, .3), skirtMat, 0, -10.55, -8.85);
  var ls = mesh(new T.BoxGeometry(120, .9, .3), skirtMat, -23.85, -10.55, 40); ls.rotation.y = Math.PI / 2;

  // Persian rug under the desk: red field, central medallion, corner pieces, border bands, paisley (boteh) and fringe
  var rugTex = canvasTex(1024, 1536, function (g, w, h) {
    var RED = '#9e1b1f', NAVY = '#1c2a5a', IVORY = '#f3e2c0', GOLD = '#d9a441', TEAL = '#2f7a78', ROSE = '#c8505a';
    function diamond(cx, cy, rx, ry, col) { g.fillStyle = col; g.beginPath(); g.moveTo(cx, cy - ry); g.lineTo(cx + rx, cy); g.lineTo(cx, cy + ry); g.lineTo(cx - rx, cy); g.closePath(); g.fill(); }
    function flower(cx, cy, r, petal, core) {
      g.fillStyle = petal;
      for (var k = 0; k < 8; k++) { var a = k / 8 * Math.PI * 2; g.beginPath(); g.ellipse(cx + Math.cos(a) * r * .55, cy + Math.sin(a) * r * .55, r * .45, r * .22, a, 0, 7); g.fill(); }
      g.fillStyle = core; g.beginPath(); g.arc(cx, cy, r * .3, 0, 7); g.fill();
    }
    function boteh(cx, cy, s, rot, col) {
      g.save(); g.translate(cx, cy); g.rotate(rot); g.scale(s, s); g.fillStyle = col;
      g.beginPath(); g.moveTo(0, 18); g.bezierCurveTo(-16, 14, -16, -6, 0, -10); g.bezierCurveTo(10, -14, 14, -24, 6, -30); g.bezierCurveTo(22, -22, 18, 14, 0, 18); g.fill();
      g.fillStyle = IVORY; g.beginPath(); g.arc(0, 4, 4, 0, 7); g.fill(); g.restore();
    }
    // border bands
    g.fillStyle = IVORY; g.fillRect(0, 0, w, h);
    g.fillStyle = NAVY; g.fillRect(14, 14, w - 28, h - 28);
    g.fillStyle = IVORY; g.fillRect(70, 70, w - 140, h - 140);
    g.fillStyle = RED; g.fillRect(84, 84, w - 168, h - 168);
    // little flowers running round the navy border
    for (var x = 50; x < w; x += 64) { flower(x, 42, 22, GOLD, ROSE); flower(x, h - 42, 22, GOLD, ROSE); }
    for (var y = 106; y < h - 70; y += 64) { flower(42, y, 22, GOLD, ROSE); flower(w - 42, y, 22, GOLD, ROSE); }
    // scattered boteh and blossoms over the red field
    for (var by = 160; by < h - 120; by += 120) for (var bx = 150; bx < w - 120; bx += 120) {
      var odd = ((bx + by) / 120) % 2 < 1;
      if (odd) boteh(bx, by, 1.4, (bx + by) % 3 - 1, GOLD); else flower(bx, by, 18, IVORY, TEAL);
    }
    // corner quarter-medallions
    [[84, 84], [w - 84, 84], [84, h - 84], [w - 84, h - 84]].forEach(function (c) {
      g.save(); g.beginPath(); g.rect(84, 84, w - 168, h - 168); g.clip();
      g.fillStyle = NAVY; g.beginPath(); g.arc(c[0], c[1], 210, 0, 7); g.fill();
      g.fillStyle = IVORY; g.beginPath(); g.arc(c[0], c[1], 170, 0, 7); g.fill();
      g.fillStyle = TEAL; g.beginPath(); g.arc(c[0], c[1], 150, 0, 7); g.fill();
      flower(c[0] + (c[0] < w / 2 ? 80 : -80), c[1] + (c[1] < h / 2 ? 80 : -80), 34, GOLD, RED);
      g.restore();
    });
    // central medallion: layered lozenge with pendants
    var cx = w / 2, cy = h / 2;
    diamond(cx, cy, 300, 470, NAVY); diamond(cx, cy, 270, 430, IVORY); diamond(cx, cy, 250, 400, TEAL);
    diamond(cx, cy - 480, 60, 70, NAVY); diamond(cx, cy + 480, 60, 70, NAVY);
    g.fillStyle = IVORY; g.beginPath(); g.ellipse(cx, cy, 170, 250, 0, 0, 7); g.fill();
    g.fillStyle = RED; g.beginPath(); g.ellipse(cx, cy, 150, 228, 0, 0, 7); g.fill();
    for (var k = 0; k < 12; k++) { var a = k / 12 * Math.PI * 2; flower(cx + Math.cos(a) * 105, cy + Math.sin(a) * 165, 26, GOLD, NAVY); }
    flower(cx, cy, 80, GOLD, NAVY); flower(cx, cy, 36, IVORY, ROSE);
    // woven texture
    for (var t = 0; t < 9000; t++) { g.fillStyle = 'rgba(0,0,0,' + Math.random() * .08 + ')'; g.fillRect(Math.random() * w, Math.random() * h, 2, 2); }
  });
  var rug = mesh(new T.PlaneGeometry(20, 30), std(0xffffff, .95, 0, { map: rugTex }), 0, -10.96, 3); rug.rotation.x = -Math.PI / 2; rug.rotation.z = Math.PI / 2; rug.castShadow = false;
  var fringeMat = std(0xf3e2c0, 1);
  [-15, 15].forEach(function (fx) {
    for (var fi2 = 0; fi2 < 50; fi2++) { var fr = mesh(new T.BoxGeometry(.7, .03, .08), fringeMat, fx + (fx < 0 ? -.35 : .35), -10.95, 3 - 9.8 + fi2 * .4); fr.castShadow = false; }
  });


  // curtains beside the window
  var curtMat = std(0xff7a3d, .9, 0, { side: T.DoubleSide });
  [-6.6, 6.6].forEach(function (dx) {
    var g = new T.PlaneGeometry(2.6, 11, 12, 1), pos = g.attributes.position;
    for (var i = 0; i < pos.count; i++) pos.setZ(i, Math.sin(pos.getX(i) * 4) * .25);
    g.computeVertexNormals();
    mesh(g, curtMat, -11 + dx, 8.4, -8.5);
  });
  mesh(new T.CylinderGeometry(.12, .12, 17, 10), std(0x1d1733, .4, .5), -11, 14.1, -8.4).rotation.z = Math.PI / 2;

  // floating shelf with books, a trophy and a tiny cactus
  var shelf = new T.Group(); shelf.position.set(3, 11.6, -8.2); scene.add(shelf);
  mesh(rbox(7.5, .35, 1.6, .1), std(0x9a6232, .6, 0, { map: woodTex }), 0, 0, 0, shelf);
  [[0x5b7cfa, 1.6], [0xff5d73, 1.9], [0x4ad6c0, 1.5], [0xffc145, 1.8], [0xf3e4c8, 1.4]].forEach(function (b, i) { mesh(rbox(.42, b[1], 1.2, .05), std(b[0], .7), -3.2 + i * .46, .17 + b[1] / 2, 0, shelf); });
  var tilted = mesh(rbox(.42, 1.6, 1.2, .05), std(0x8b5cf6, .7), -.75, .95, 0, shelf); tilted.rotation.z = -.35;
  mesh(new T.CylinderGeometry(.35, .25, .9, 24), std(0xffc145, .25, .9), 1.4, .62, 0, shelf);
  mesh(new T.SphereGeometry(.45, 24, 16), std(0xffc145, .25, .9), 1.4, 1.35, 0, shelf);
  mesh(new T.CylinderGeometry(.45, .35, .6, 20), std(0xd9693b, .7), 2.9, .47, 0, shelf);
  mesh(new T.CylinderGeometry(.25, .25, 1.1, 12), std(0x3faa5b, .6), 2.9, 1.2, 0, shelf);

  // wall clock showing the visitor's real time
  var clock3 = new T.Group(); clock3.position.set(-3.5, 15.6, -8.8); scene.add(clock3);
  mesh(new T.CylinderGeometry(1.7, 1.7, .3, 48), std(0x1d1733, .4, .3), 0, 0, 0, clock3).rotation.x = Math.PI / 2;
  mesh(new T.CircleGeometry(1.45, 48), std(0xfff8ea, .6), 0, 0, .17, clock3);
  for (var hI = 0; hI < 12; hI++) { var a12 = hI / 12 * Math.PI * 2, tk = mesh(new T.BoxGeometry(.1, hI % 3 ? .18 : .35, .02), std(0x1d1733, .5), Math.sin(a12) * 1.2, Math.cos(a12) * 1.2, .19, clock3); tk.rotation.z = -a12; }
  function hand(len, wdt, col) { var pv = new T.Group(); pv.position.z = .22; clock3.add(pv); mesh(new T.BoxGeometry(wdt, len, .04), std(col, .4), 0, len / 2 - .1, 0, pv); return pv; }
  var hourH = hand(.75, .12, 0x1d1733), minH = hand(1.15, .08, 0x1d1733), secH = hand(1.25, .04, 0xff5d73);

  // neon sign
  var neonTex = canvasTex(512, 200, function (g) {
    g.font = '900 150px Poppins, monospace'; g.textAlign = 'center'; g.textBaseline = 'middle';
    g.shadowColor = '#ff5d73'; g.shadowBlur = 28; g.fillStyle = '#ffd1d8'; g.fillText('</>', 256, 104); g.fillText('</>', 256, 104);
  });
  var neon = new T.Mesh(new T.PlaneGeometry(6, 2.35), new T.MeshBasicMaterial({ map: neonTex, transparent: true, toneMapped: false, depthWrite: false }));
  neon.position.set(4, 16.6, -8.8); scene.add(neon);
  var neonLight = new T.PointLight(0xff5d73, .6, 10, 2); neonLight.position.set(4, 16.4, -7.5); scene.add(neonLight);

  // fairy lights along the top of the wall
  var bulbs = [], bulbCols = [0xffd36b, 0xff9a5a, 0xfff1c9, 0xff7a8a];
  var wire = [];
  for (var fi = 0; fi <= 40; fi++) { var fx = -23 + fi * 1.1, sag = Math.abs(Math.sin(fi / 40 * Math.PI * 3)) * .9; wire.push(new T.Vector3(fx, 19.4 - sag, -8.7)); }
  scene.add(new T.Line(new T.BufferGeometry().setFromPoints(wire), new T.LineBasicMaterial({ color: 0x1d1733 })));
  wire.forEach(function (v, i) { if (i % 2) return; var b = new T.Mesh(new T.SphereGeometry(.22, 12, 10), new T.MeshBasicMaterial({ color: bulbCols[i % 4], toneMapped: false })); b.position.copy(v).add(new T.Vector3(0, -.3, .05)); scene.add(b); bulbs.push(b); });

  // bookshelf on the right
  var bs = new T.Group(); bs.position.set(17.5, -11, -6.8); scene.add(bs);
  var bsMat = std(0x9a6232, .6, 0, { map: woodTex });
  mesh(rbox(.5, 17, 3.6, .1), bsMat, -3.2, 8.5, 0, bs); mesh(rbox(.5, 17, 3.6, .1), bsMat, 3.2, 8.5, 0, bs);
  mesh(new T.BoxGeometry(6.4, 17, .2), std(0x6b4423, .8), 0, 8.5, -1.7, bs);
  var bookCols = [0x5b7cfa, 0xff5d73, 0x4ad6c0, 0xffc145, 0xf3e4c8, 0x8b5cf6, 0xff7a3d, 0x2e8a48];
  var seed = 7; function rnd() { seed = (seed * 9301 + 49297) % 233280; return seed / 233280; }
  [0, 4.2, 8.4, 12.6, 16.8].forEach(function (y, si) {
    mesh(rbox(6.4, .35, 3.4, .08), bsMat, 0, y + .17, 0, bs);
    if (si === 4) return;
    var x = -2.75, n = 0;
    while (x < 2.4) {
      if (si === 1 && x > .5) { mesh(new T.SphereGeometry(.7, 24, 16), std(0x2a2346, .3, .2), 1.6, y + 1.05, 0, bs); break; }
      var bw = .3 + rnd() * .25, bh = 2.4 + rnd() * 1.1;
      var bk = mesh(rbox(bw, bh, 2.3, .04), std(bookCols[Math.floor(rnd() * bookCols.length)], .7), x + bw / 2, y + .35 + bh / 2, .2, bs);
      if (si === 2 && n === 9) { bk.rotation.z = -.3; bk.position.x += .4; break; }
      x += bw + .04; n++;
    }
  });
  mesh(new T.CylinderGeometry(.7, .55, 1, 24), std(0xd9693b, .7), 0, 17.5, 0, bs);
  for (var vi = 0; vi < 7; vi++) { var vine = mesh(new T.SphereGeometry(.35, 12, 10), std(0x3faa5b, .6), -.6 + Math.sin(vi) * .5, 17.2 - vi * .6, .9 + Math.cos(vi) * .3, bs); vine.scale.set(1, .6, 1); }

  // big floor plant in the corner
  var fp = new T.Group(); fp.position.set(-18.5, -11, -4.5); scene.add(fp);
  mesh(new T.LatheGeometry([new T.Vector2(0, 0), new T.Vector2(1.6, 0), new T.Vector2(2.1, 4), new T.Vector2(2.3, 4.1), new T.Vector2(2.3, 4.5), new T.Vector2(2, 4.5)], 48), std(0xf3e4c8, .6), 0, 0, 0, fp);
  var fpSoil = mesh(new T.CircleGeometry(2.05, 40), std(0x3a2414, 1), 0, 4.32, 0, fp); fpSoil.rotation.x = -Math.PI / 2; fpSoil.castShadow = false;
  for (var bl = 0; bl < 14; bl++) {
    var piv = new T.Group(); piv.position.y = 4.3; piv.rotation.y = bl / 14 * Math.PI * 2 + bl * .4; fp.add(piv);
    var big = mesh(leafGeo, std(bl % 2 ? 0x2e8a48 : 0x3faa5b, .55), 0, 0, 0, piv);
    big.scale.setScalar(2 + (bl % 3) * .5); big.rotation.x = -.15 - (bl % 4) * .18;
    leaves.push({ p: piv, l: big, base: big.rotation.x, k: bl + 20 });
  }

  // office chair, pulled out a little
  var chair = new T.Group(); chair.position.set(4.2, -11, 10.2); chair.rotation.y = -.45; scene.add(chair);
  var dark = std(0x1d1733, .4, .4), seatMat = std(0xff7a3d, .75);
  for (var cl = 0; cl < 5; cl++) {
    var ca = cl / 5 * Math.PI * 2, leg = mesh(rbox(.45, .3, 2.6, .1), dark, Math.sin(ca) * 1.3, .9, Math.cos(ca) * 1.3, chair);
    leg.rotation.y = ca;
    mesh(new T.SphereGeometry(.35, 16, 12), dark, Math.sin(ca) * 2.5, .35, Math.cos(ca) * 2.5, chair);
  }
  mesh(new T.CylinderGeometry(.25, .3, 4.6, 16), std(0xc9c3d8, .3, .7), 0, 3.3, 0, chair);
  mesh(rbox(4.6, .9, 4.4, .4), seatMat, 0, 6, 0, chair);
  var back = mesh(rbox(4.4, 5.2, .8, .4), seatMat, 0, 9.4, -2.1, chair); back.rotation.x = -.12;
  mesh(rbox(.5, 2.8, .4, .15), dark, 0, 7.2, -2, chair);

  // headphones resting on the desk
  var hp = new T.Group(); hp.position.set(-8.2, .58, 3.6); hp.rotation.y = .5; scene.add(hp);
  var band = mesh(new T.TorusGeometry(1.05, .14, 12, 32, Math.PI), std(0x1d1733, .4, .3), 0, 0, 0, hp); band.rotation.x = -Math.PI / 2;
  [-1.05, 1.05].forEach(function (x) { var cupH = mesh(new T.CylinderGeometry(.55, .55, .45, 32), std(0xff5d73, .4), x, .1, 0, hp); cupH.rotation.z = Math.PI / 2; });

  // waste bin with a few crumpled ideas
  mesh(new T.CylinderGeometry(1.2, 1, 2.8, 32, 1, true), std(0x4ad6c0, .5, 0, { side: T.DoubleSide }), 12.5, -9.6, -1.5);
  [[12.1, -9.2, -1.3], [10.4, -10.6, .2], [13.8, -10.6, 1.4], [11.2, -10.6, 2.6]].forEach(function (b) { var pb = mesh(new T.IcosahedronGeometry(.45, 0), std(0xfaf6ee, .9), b[0], b[1], b[2]); pb.rotation.set(b[0], b[1], b[2]); });

  /* ---------- lights ---------- */
  var hemi = new T.HemisphereLight(0x9fa8ff, 0x2a1640, .55); scene.add(hemi);
  var key = new T.SpotLight(0xffc98a, 2.2, 40, .75, .6, 1.2);
  key.position.copy(LC).add(LT.clone().sub(LC).normalize().multiplyScalar(1.1)); key.target.position.copy(LT); scene.add(key); scene.add(key.target);
  key.castShadow = true; key.shadow.mapSize.set(lite ? 512 : 1024, lite ? 512 : 1024); key.shadow.bias = -.0004; key.shadow.radius = 4;
  var fill = new T.DirectionalLight(0xb9b0ff, .55); fill.position.set(10, 18, 14); scene.add(fill);
  fill.castShadow = true; fill.shadow.mapSize.set(lite ? 1024 : 2048, lite ? 1024 : 2048); fill.shadow.camera.left = -28; fill.shadow.camera.right = 28; fill.shadow.camera.top = 28; fill.shadow.camera.bottom = -28; fill.shadow.camera.far = 80; fill.shadow.bias = -.0005;
  var glow = new T.PointLight(0x8fb4ff, .9, 14, 2); glow.position.set(0, SCREEN_Y, SCREEN_Z + 2.5); scene.add(glow);

  function applyTheme() {
    var day = root.dataset.theme === 'day';
    scene.background = new T.Color(day ? 0xf6dfc0 : 0x120c2a);
    scene.fog.color.set(day ? 0xf6dfc0 : 0x120c2a);
    wallMat.color.set(day ? 0xf2d7b3 : 0x2b2150); floorMat.color.set(day ? 0xc79a72 : 0x3a2a4a);
    hemi.intensity = day ? .85 : .55; hemi.color.set(day ? 0xfff3e0 : 0x9fa8ff);
    fill.intensity = day ? 1.1 : .55; fill.color.set(day ? 0xfff1d6 : 0xb9b0ff);
    key.intensity = day ? 1.2 : 2.2; glow.intensity = day ? .35 : .9;
    cover.material.color.set(getComputedStyle(root).getPropertyValue('--bg').trim() || '#0a0720');
    paintSky(); paintScreen(); dirty = true;
  }

  /* ---------- camera path driven by scroll ---------- */
  var P = 0, mx = 0, my = 0, smx = 0, smy = 0, dirty = true;
  var ss = function (a, b, x) { x = Math.max(0, Math.min(1, (x - a) / (b - a))); return x * x * (3 - 2 * x); };
  var lerp = function (a, b, t) { return a + (b - a) * t; };
  var tmp = new T.Vector3(), look = new T.Vector3();
  function screenDistance() {
    var vfov = camera.fov * Math.PI / 180, th = Math.tan(vfov / 2);
    return Math.min((SCREEN_H / 2) / th, (SCREEN_W / 2) / (th * camera.aspect)) * .985;   // screen just covers the viewport
  }
  function placeCamera() {
    var orbit = ss(0, .68, P), push = ss(.64, .97, P);
    var ang = lerp(-1.25, .42, orbit), rad = lerp(36, 21, orbit), hgt = lerp(17, 8.5, orbit);
    var k = Math.min(2.2, Math.max(1, 1.25 / camera.aspect));   // pull back on narrow (phone) screens
    rad *= k; hgt *= (1 + (k - 1) * .6);
    tmp.set(Math.sin(ang) * rad, hgt, Math.cos(ang) * rad);
    look.set(lerp(-.5, 0, orbit), lerp(-2.2, 3.4, orbit), lerp(0, -1, orbit));
    // push in towards the screen centre
    var end = new T.Vector3(0, SCREEN_Y, SCREEN_Z + screenDistance()), endLook = new T.Vector3(0, SCREEN_Y, SCREEN_Z);
    tmp.lerp(end, push); look.lerp(endLook, push);
    var par = (1 - push) * (reduce ? 0 : 1);           // gentle mouse parallax, gone at the end
    tmp.x += smx * 1.6 * par; tmp.y += -smy * .9 * par;
    camera.position.copy(tmp); camera.lookAt(look);
    cover.material.opacity = ss(.86, .97, P);
  }

  /* ---------- loop ---------- */
  var section = document.getElementById('top'), clock = new T.Clock(), visible = true, lastP = -1, lastDraw = -1;
  function size() {
    var w = canvas.clientWidth, h = canvas.clientHeight;
    renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix(); dirty = true;
    var kf = Math.min(2.2, Math.max(1, 1.25 / camera.aspect)); scene.fog.near = 40 * kf; scene.fog.far = 90 * kf;
  }
  function frame() {
    requestAnimationFrame(frame);
    var r = section.getBoundingClientRect();
    visible = r.bottom > 0 && r.top < innerHeight;
    if (!visible) return;
    var t = clock.getElapsedTime();
    // scrolling moves the camera, so draw right away; otherwise only tick the small idle animations at a capped rate
    var moved = P !== lastP || dirty || Math.abs(mx - smx) > .002 || Math.abs(my - smy) > .002;
    if (!moved && t - lastDraw < (lite ? 1 / 15 : 1 / 30)) return;
    lastP = P; lastDraw = t; dirty = false;
    smx += (mx - smx) * .05; smy += (my - smy) * .05;
    if (!reduce) {
      steam.forEach(function (sp) {
        var k = (t * .25 + sp.userData.off) % 1;
        sp.position.set(Math.sin(k * 6 + sp.userData.off * 9) * .3, 2.2 + k * 3.2, Math.cos(k * 5) * .2);
        sp.scale.setScalar(.6 + k * 1.4); sp.material.opacity = Math.sin(k * Math.PI) * .45;
      });
      leaves.forEach(function (o) { o.l.rotation.x = o.base + Math.sin(t * 1.3 + o.k) * .04; });
    }
    var now = new Date(), sec = now.getSeconds() + now.getMilliseconds() / 1000, min = now.getMinutes() + sec / 60, hr = now.getHours() % 12 + min / 60;
    secH.rotation.z = -sec / 60 * Math.PI * 2; minH.rotation.z = -min / 60 * Math.PI * 2; hourH.rotation.z = -hr / 12 * Math.PI * 2;
    if (!reduce) bulbs.forEach(function (b, i) { b.scale.setScalar(.85 + Math.sin(t * 2 + i) * .15); });
    if (t - skyT > .35) { skyT = t; if (root.dataset.theme !== 'day') paintSky(); }
    placeCamera();
    renderer.render(scene, camera);
  }
  addEventListener('pointermove', function (e) { mx = e.clientX / innerWidth - .5; my = e.clientY / innerHeight - .5; }, { passive: true });
  addEventListener('resize', size);
  new MutationObserver(applyTheme).observe(root, { attributes: true, attributeFilter: ['data-theme', 'lang'] });
  size(); applyTheme();
  (document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve()).then(paintScreen);
  requestAnimationFrame(frame);

  window.Desk3D = { progress: function (p) { P = p; }, redraw: paintScreen };
})();
