# 🌐 Configuración de Cloudflare Tunnel

## Pasos para configurar el Tunnel

### 1. Crear el Tunnel en Cloudflare

1. Ve a [Cloudflare Zero Trust Dashboard](https://one.dash.cloudflare.com/)
2. En el menú lateral, selecciona **Networks > Tunnels**
3. Haz clic en **Create a tunnel**
4. Selecciona **Cloudflared** como tipo de tunnel
5. Dale un nombre al tunnel: `kiosco-credencializacion`
6. Haz clic en **Save tunnel**

### 2. Configurar el Tunnel

1. En la página del tunnel, copia el **Tunnel Token** (es un string largo que empieza con `eyJ...`)
2. Configura el Public Hostname:
   - **Public hostname**: `credencializacion.prome.works`
   - **Service type**: `HTTP`
   - **URL**: `app:80`
   
   > **Importante**: Usa `app:80` (no localhost:3001) porque el contenedor cloudflared se comunica con el contenedor app mediante la red interna de Docker.

3. Guarda la configuración

### 3. Configurar el proyecto

1. Copia el archivo `.env.example` a `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edita el archivo `.env` y pega tu token:
   ```bash
   CLOUDFLARE_TUNNEL_TOKEN=eyJhIjoiTU9fVE9LRU5fQVFVSQ...
   ```

3. Ajusta las contraseñas si lo deseas:
   ```bash
   SEED_DEFAULT_PASSWORD=tu_password_seguro
   SEED_ADMIN_PASSWORD=tu_password_admin_seguro
   ```

### 4. Levantar el proyecto

```bash
docker-compose up -d --build
```

### 5. Verificar que funciona

1. Verifica que los contenedores estén corriendo:
   ```bash
   docker-compose ps
   ```

2. Verifica los logs del tunnel:
   ```bash
   docker-compose logs -f tunnel
   ```
   
   Deberías ver algo como:
   ```
   INF Connection registered connIndex=0 connection=...
   INF Registered tunnel connection
   ```

3. Accede a tu aplicación en: **https://credencializacion.prome.works**

4. Acceso local (opcional): **http://localhost:3001**

## 📊 Comandos útiles

```bash
# Ver logs de todos los servicios
docker-compose logs -f

# Ver logs solo del tunnel
docker-compose logs -f tunnel

# Ver logs de la aplicación
docker-compose logs -f app

# Reiniciar solo el tunnel
docker-compose restart tunnel

# Detener todo
docker-compose down

# Detener y eliminar volúmenes (⚠️ borra la base de datos)
docker-compose down -v
```

## 🔧 Troubleshooting

### El tunnel no se conecta
- Verifica que el token sea correcto
- Verifica que el dominio esté configurado correctamente en Cloudflare
- Revisa los logs: `docker-compose logs tunnel`

### La aplicación no carga
- Verifica que la base de datos esté saludable: `docker-compose ps`
- Revisa los logs de la app: `docker-compose logs app`
- Accede localmente primero: http://localhost:3001

### Error de CSRF
- Asegúrate de que `app.baseURL` en `.env` apunte a `https://credencializacion.prome.works/`

## 📝 Arquitectura

```
Internet
   ↓
Cloudflare Tunnel (credencializacion.prome.works)
   ↓
Docker Network (kiosco_network)
   ↓
Container: kiosco_tunnel → Container: kiosco_app (port 80)
   ↓
Host: localhost:3001 → Container: kiosco_app (port 80)
```
