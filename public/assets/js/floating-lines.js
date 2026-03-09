import {
  Scene,
  OrthographicCamera,
  WebGLRenderer,
  PlaneGeometry,
  Mesh,
  ShaderMaterial,
  Vector3,
  Vector2,
  Clock
} from 'https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js';

const vertexShader = `
precision highp float;
void main() {
  gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
}
`;

const fragmentShader = `
// (PEGAR AQUÍ tu fragmentShader COMPLETO tal cual lo tienes)
${String.raw`precision highp float;

uniform float iTime;
uniform vec3  iResolution;
uniform float animationSpeed;

uniform bool enableTop;
uniform bool enableMiddle;
uniform bool enableBottom;

uniform int topLineCount;
uniform int middleLineCount;
uniform int bottomLineCount;

uniform float topLineDistance;
uniform float middleLineDistance;
uniform float bottomLineDistance;

uniform vec3 topWavePosition;
uniform vec3 middleWavePosition;
uniform vec3 bottomWavePosition;

uniform vec2 iMouse;
uniform bool interactive;
uniform float bendRadius;
uniform float bendStrength;
uniform float bendInfluence;

uniform bool parallax;
uniform float parallaxStrength;
uniform vec2 parallaxOffset;

uniform vec3 lineGradient[8];
uniform int lineGradientCount;

const vec3 BLACK = vec3(0.0);
const vec3 PINK  = vec3(233.0, 71.0, 245.0) / 255.0;
const vec3 BLUE  = vec3(47.0,  75.0, 162.0) / 255.0;

mat2 rotate(float r) {
  return mat2(cos(r), sin(r), -sin(r), cos(r));
}

vec3 background_color(vec2 uv) {
  vec3 col = vec3(0.0);

  float y = sin(uv.x - 0.2) * 0.3 - 0.1;
  float m = uv.y - y;

  col += mix(BLUE, BLACK, smoothstep(0.0, 1.0, abs(m)));
  col += mix(PINK, BLACK, smoothstep(0.0, 1.0, abs(m - 0.8)));
  return col * 0.5;
}

vec3 getLineColor(float t, vec3 baseColor) {
  if (lineGradientCount <= 0) {
    return baseColor;
  }

  vec3 gradientColor;

  if (lineGradientCount == 1) {
    gradientColor = lineGradient[0];
  } else {
    float clampedT = clamp(t, 0.0, 0.9999);
    float scaled = clampedT * float(lineGradientCount - 1);
    int idx = int(floor(scaled));
    float f = fract(scaled);
    int idx2 = min(idx + 1, lineGradientCount - 1);

    vec3 c1 = lineGradient[idx];
    vec3 c2 = lineGradient[idx2];

    gradientColor = mix(c1, c2, f);
  }

  return gradientColor * 0.5;
}

float wave(vec2 uv, float offset, vec2 screenUv, vec2 mouseUv, bool shouldBend) {
  float time = iTime * animationSpeed;

  float x_offset   = offset;
  float x_movement = time * 0.1;
  float amp        = sin(offset + time * 0.2) * 0.3;
  float y          = sin(uv.x + x_offset + x_movement) * amp;

  if (shouldBend) {
    vec2 d = screenUv - mouseUv;
    float influence = exp(-dot(d, d) * bendRadius);
    float bendOffset = (mouseUv.y - screenUv.y) * influence * bendStrength * bendInfluence;
    y += bendOffset;
  }

  float m = uv.y - y;
  return 0.0175 / max(abs(m) + 0.01, 1e-3) + 0.01;
}

void mainImage(out vec4 fragColor, in vec2 fragCoord) {
  vec2 baseUv = (2.0 * fragCoord - iResolution.xy) / iResolution.y;
  baseUv.y *= -1.0;

  if (parallax) {
    baseUv += parallaxOffset;
  }

  vec3 col = vec3(0.0);
  vec3 b = lineGradientCount > 0 ? vec3(0.0) : background_color(baseUv);

  vec2 mouseUv = vec2(0.0);
  if (interactive) {
    mouseUv = (2.0 * iMouse - iResolution.xy) / iResolution.y;
    mouseUv.y *= -1.0;
  }

  if (enableBottom) {
    for (int i = 0; i < bottomLineCount; ++i) {
      float fi = float(i);
      float t = fi / max(float(bottomLineCount - 1), 1.0);
      vec3 lineCol = getLineColor(t, b);

      float angle = bottomWavePosition.z * log(length(baseUv) + 1.0);
      vec2 ruv = baseUv * rotate(angle);
      col += lineCol * wave(
        ruv + vec2(bottomLineDistance * fi + bottomWavePosition.x, bottomWavePosition.y),
        1.5 + 0.2 * fi,
        baseUv,
        mouseUv,
        interactive
      ) * 0.2;
    }
  }

  if (enableMiddle) {
    for (int i = 0; i < middleLineCount; ++i) {
      float fi = float(i);
      float t = fi / max(float(middleLineCount - 1), 1.0);
      vec3 lineCol = getLineColor(t, b);

      float angle = middleWavePosition.z * log(length(baseUv) + 1.0);
      vec2 ruv = baseUv * rotate(angle);
      col += lineCol * wave(
        ruv + vec2(middleLineDistance * fi + middleWavePosition.x, middleWavePosition.y),
        2.0 + 0.15 * fi,
        baseUv,
        mouseUv,
        interactive
      );
    }
  }

  if (enableTop) {
    for (int i = 0; i < topLineCount; ++i) {
      float fi = float(i);
      float t = fi / max(float(topLineCount - 1), 1.0);
      vec3 lineCol = getLineColor(t, b);

      float angle = topWavePosition.z * log(length(baseUv) + 1.0);
      vec2 ruv = baseUv * rotate(angle);
      ruv.x *= -1.0;
      col += lineCol * wave(
        ruv + vec2(topLineDistance * fi + topWavePosition.x, topWavePosition.y),
        1.0 + 0.2 * fi,
        baseUv,
        mouseUv,
        interactive
      ) * 0.1;
    }
  }

  fragColor = vec4(col, 1.0);
}

void main() {
  vec4 color = vec4(0.0);
  mainImage(color, gl_FragCoord.xy);
  gl_FragColor = color;
}`}
`;

const MAX_GRADIENT_STOPS = 8;

function hexToVec3(hex) {
  let value = hex.trim();
  if (value.startsWith('#')) value = value.slice(1);

  let r = 255, g = 255, b = 255;
  if (value.length === 3) {
    r = parseInt(value[0] + value[0], 16);
    g = parseInt(value[1] + value[1], 16);
    b = parseInt(value[2] + value[2], 16);
  } else if (value.length === 6) {
    r = parseInt(value.slice(0,2), 16);
    g = parseInt(value.slice(2,4), 16);
    b = parseInt(value.slice(4,6), 16);
  }
  return new Vector3(r/255, g/255, b/255);
}

function mountFloatingLines(el, opts = {}) {
  const scene = new Scene();
  const camera = new OrthographicCamera(-1, 1, 1, -1, 0, 1);
  camera.position.z = 1;

  const renderer = new WebGLRenderer({ antialias: true, alpha: false });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.domElement.style.width = '100%';
  renderer.domElement.style.height = '100%';
  el.appendChild(renderer.domElement);

  const enabledWaves = opts.enabledWaves ?? ['top','middle','bottom'];
  const interactive  = opts.interactive ?? true;
  const parallax     = opts.parallax ?? true;

  const lineCount = opts.lineCount ?? [6, 6, 6];
  const lineDistance = opts.lineDistance ?? [5, 5, 5];

  const getLineCount = (waveType) => {
    const idx = enabledWaves.indexOf(waveType);
    return idx >= 0 ? (lineCount[idx] ?? 6) : 0;
  };
  const getLineDistance = (waveType) => {
    const idx = enabledWaves.indexOf(waveType);
    return idx >= 0 ? ((lineDistance[idx] ?? 5) * 0.01) : 0.01;
  };

  const uniforms = {
    iTime: { value: 0 },
    iResolution: { value: new Vector3(1, 1, 1) },
    animationSpeed: { value: opts.animationSpeed ?? 1 },

    enableTop: { value: enabledWaves.includes('top') },
    enableMiddle: { value: enabledWaves.includes('middle') },
    enableBottom: { value: enabledWaves.includes('bottom') },

    topLineCount: { value: getLineCount('top') },
    middleLineCount: { value: getLineCount('middle') },
    bottomLineCount: { value: getLineCount('bottom') },

    topLineDistance: { value: getLineDistance('top') },
    middleLineDistance: { value: getLineDistance('middle') },
    bottomLineDistance: { value: getLineDistance('bottom') },

    topWavePosition: { value: new Vector3(opts.topWavePosition?.x ?? 10.0, opts.topWavePosition?.y ?? 0.5, opts.topWavePosition?.rotate ?? -0.4) },
    middleWavePosition: { value: new Vector3(opts.middleWavePosition?.x ?? 5.0, opts.middleWavePosition?.y ?? 0.0, opts.middleWavePosition?.rotate ?? 0.2) },
    bottomWavePosition: { value: new Vector3(opts.bottomWavePosition?.x ?? 2.0, opts.bottomWavePosition?.y ?? -0.7, opts.bottomWavePosition?.rotate ?? 0.4) },

    iMouse: { value: new Vector2(-1000, -1000) },
    interactive: { value: interactive },
    bendRadius: { value: opts.bendRadius ?? 5.0 },
    bendStrength: { value: opts.bendStrength ?? -0.5 },
    bendInfluence: { value: 0 },

    parallax: { value: parallax },
    parallaxStrength: { value: opts.parallaxStrength ?? 0.2 },
    parallaxOffset: { value: new Vector2(0, 0) },

    lineGradient: { value: Array.from({ length: MAX_GRADIENT_STOPS }, () => new Vector3(1,1,1)) },
    lineGradientCount: { value: 0 }
  };

  const linesGradient = opts.linesGradient ?? ['#1E3A8A', '#2F6DF6', '#93C5FD'];
  if (linesGradient.length) {
    const stops = linesGradient.slice(0, MAX_GRADIENT_STOPS);
    uniforms.lineGradientCount.value = stops.length;
    stops.forEach((hex, i) => {
      const c = hexToVec3(hex);
      uniforms.lineGradient.value[i].set(c.x, c.y, c.z);
    });
  }

  const material = new ShaderMaterial({ uniforms, vertexShader, fragmentShader });
  const geometry = new PlaneGeometry(2, 2);
  const mesh = new Mesh(geometry, material);
  scene.add(mesh);

  const clock = new Clock();

  const targetMouse = new Vector2(-1000, -1000);
  const currentMouse = new Vector2(-1000, -1000);
  let targetInfluence = 0, currentInfluence = 0;

  const targetParallax = new Vector2(0,0);
  const currentParallax = new Vector2(0,0);

  const mouseDamping = opts.mouseDamping ?? 0.05;

  const setSize = () => {
    const width = el.clientWidth || 1;
    const height = el.clientHeight || 1;
    renderer.setSize(width, height, false);
    uniforms.iResolution.value.set(renderer.domElement.width, renderer.domElement.height, 1);
  };
  setSize();

  const ro = (typeof ResizeObserver !== 'undefined') ? new ResizeObserver(setSize) : null;
  ro?.observe(el);

  const onMove = (event) => {
    const rect = renderer.domElement.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const y = event.clientY - rect.top;
    const dpr = renderer.getPixelRatio();

    targetMouse.set(x * dpr, (rect.height - y) * dpr);
    targetInfluence = 1.0;

    if (parallax) {
      const cx = rect.width / 2;
      const cy = rect.height / 2;
      const ox = (x - cx) / rect.width;
      const oy = -(y - cy) / rect.height;
      targetParallax.set(ox * (opts.parallaxStrength ?? 0.2), oy * (opts.parallaxStrength ?? 0.2));
    }
  };
  const onLeave = () => { targetInfluence = 0.0; };

  if (interactive) {
    renderer.domElement.addEventListener('pointermove', onMove);
    renderer.domElement.addEventListener('pointerleave', onLeave);
  }

  let raf = 0;
  const loop = () => {
    uniforms.iTime.value = clock.getElapsedTime();

    if (interactive) {
      currentMouse.lerp(targetMouse, mouseDamping);
      uniforms.iMouse.value.copy(currentMouse);

      currentInfluence += (targetInfluence - currentInfluence) * mouseDamping;
      uniforms.bendInfluence.value = currentInfluence;
    }

    if (parallax) {
      currentParallax.lerp(targetParallax, mouseDamping);
      uniforms.parallaxOffset.value.copy(currentParallax);
    }

    renderer.render(scene, camera);
    raf = requestAnimationFrame(loop);
  };
  loop();

  return () => {
    cancelAnimationFrame(raf);
    ro?.disconnect();
    if (interactive) {
      renderer.domElement.removeEventListener('pointermove', onMove);
      renderer.domElement.removeEventListener('pointerleave', onLeave);
    }
    geometry.dispose();
    material.dispose();
    renderer.dispose();
    renderer.domElement.remove();
  };
}

const el = document.getElementById('floatingLines');
if (el) {
  // Inicia con colores institucionales (azul/morado)
  mountFloatingLines(el, {
    linesGradient: ['#1E3A8A', '#2F6DF6', '#93C5FD'],
    interactive: false,
    parallax: false,
    animationSpeed: 1,
  });
}