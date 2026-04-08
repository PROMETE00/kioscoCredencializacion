# 🔧 SOLUCIÓN AL ERROR 502

## ❌ Problema Identificado

El tunnel **SÍ está conectado** ✅, pero está mal configurado en Cloudflare.

**Configuración Actual (INCORRECTA):**
```
Service: http://localhost:3001
```

**Por qué falla:**
- El tunnel corre dentro de un contenedor Docker
- Desde dentro del contenedor, `localhost` se refiere al propio contenedor del tunnel
- El contenedor del tunnel NO tiene la app en el puerto 3001
- Por eso Cloudflare recibe 502 (Bad Gateway)

## ✅ Solución

Debes cambiar la configuración en Cloudflare Dashboard:

### Paso 1: Accede al Dashboard
1. Ve a: https://one.dash.cloudflare.com/
2. Click en **Networks** > **Tunnels**
3. Busca el tunnel con ID: `263348a1-179a-4555-82a7-8f6c60a9ddbf`

### Paso 2: Edita la Configuración
1. Click en el tunnel
2. Ve a la pestaña **Public Hostname**
3. Edita la ruta de `credencializacion.prome.works`
4. Cambia:
   ```
   Service Type: HTTP
   URL: app:80          👈 IMPORTANTE: Debe ser "app:80" NO "localhost:3001"
   ```

### Paso 3: Guarda y Espera
1. Click **Save**
2. Espera 30 segundos
3. El tunnel se actualizará automáticamente (no necesitas reiniciar)

### Paso 4: Verifica
```bash
curl -I https://credencializacion.prome.works
```

Deberías ver `HTTP/2 200 OK` ✅

## 📊 Explicación Técnica

```
┌─────────────────────────────────────────────────────────┐
│  Internet → Cloudflare → Tunnel Container               │
│                               ↓                          │
│                        ❌ localhost:3001 (NO EXISTE)     │
│                        ✅ app:80 (CORRECTO)             │
│                               ↓                          │
│                        App Container (puerto 80)         │
└─────────────────────────────────────────────────────────┘
```

**Por qué `app:80` funciona:**
- `app` es el nombre del servicio en docker-compose.yml
- Docker crea un DNS interno que resuelve `app` → IP del contenedor
- Los contenedores en la misma red pueden comunicarse por nombre

## 🔍 Estado Actual

✅ App funcionando: http://localhost:3001  
✅ Tunnel conectado: 4 conexiones registradas  
✅ Token configurado correctamente  
❌ Configuración en Cloudflare apunta a localhost:3001  

**Acción requerida:** Cambiar `localhost:3001` → `app:80` en Cloudflare Dashboard

---

**No necesitas:**
- ❌ Reiniciar contenedores
- ❌ Cambiar el código
- ❌ Modificar docker-compose.yml
- ❌ Regenerar el token

**Solo necesitas:**
- ✅ Cambiar la URL en Cloudflare Dashboard
