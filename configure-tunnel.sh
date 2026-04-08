#!/bin/bash

echo "============================================================================="
echo "  🔧 CONFIGURADOR DE TUNNEL PARA KIOSCO DE CREDENCIALIZACIÓN"
echo "============================================================================="
echo ""
echo "El dominio credencializacion.prome.works YA EXISTE en Cloudflare"
echo "Solo necesitas pegar el TUNNEL TOKEN"
echo ""
echo "============================================================================="
echo ""

# Pedir el token al usuario
read -p "Pega aquí el Cloudflare Tunnel Token (empieza con eyJ...): " TUNNEL_TOKEN

if [ -z "$TUNNEL_TOKEN" ]; then
    echo "❌ Error: No ingresaste ningún token"
    exit 1
fi

if [[ ! $TUNNEL_TOKEN == eyJ* ]]; then
    echo "⚠️  Advertencia: El token no parece válido (debería empezar con 'eyJ')"
    read -p "¿Continuar de todos modos? (s/n): " confirm
    if [[ $confirm != "s" ]]; then
        exit 1
    fi
fi

# Actualizar el archivo .env
echo ""
echo "📝 Actualizando archivo .env..."
sed -i "s|^CLOUDFLARE_TUNNEL_TOKEN=.*|CLOUDFLARE_TUNNEL_TOKEN=$TUNNEL_TOKEN|" .env

echo "✅ Token guardado en .env"
echo ""

# Levantar el tunnel
echo "🚀 Levantando el tunnel..."
docker compose up -d tunnel

echo ""
echo "⏳ Esperando 10 segundos para que el tunnel se conecte..."
sleep 10

echo ""
echo "📋 Logs del tunnel:"
echo "============================================================================="
docker compose logs --tail=20 tunnel

echo ""
echo "============================================================================="
echo ""
echo "✅ Configuración completa!"
echo ""
echo "Verifica el estado:"
echo "  - Logs: docker compose logs -f tunnel"
echo "  - Web:  https://credencializacion.prome.works"
echo ""
echo "============================================================================="
