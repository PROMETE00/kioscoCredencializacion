#!/bin/bash

echo "=========================================="
echo "PROBANDO CONEXIÓN DEL TUNNEL"
echo "=========================================="
echo ""

# Test desde el tunnel hacia el app
echo "1. Probando conectividad interna (tunnel → app)..."
docker exec kiosco_app wget -qO- --timeout=2 http://app:80 > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "   ✅ Tunnel puede resolver 'app'"
else
    echo "   ❌ Tunnel NO puede resolver 'app'"
fi

# Test local
echo ""
echo "2. Probando acceso local (host → app)..."
curl -sI -m 2 http://localhost:3001 | grep "HTTP" > /dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ App accesible en localhost:3001"
else
    echo "   ❌ App NO accesible en localhost:3001"
fi

# Test externo
echo ""
echo "3. Probando acceso externo (Cloudflare → app)..."
RESPONSE=$(curl -sI -m 5 https://credencializacion.prome.works | grep "HTTP")
echo "   Respuesta: $RESPONSE"

if echo "$RESPONSE" | grep -q "200"; then
    echo "   ✅ SITIO FUNCIONANDO"
elif echo "$RESPONSE" | grep -q "502"; then
    echo "   ❌ Error 502 - Tunnel no puede conectarse al app"
    echo ""
    echo "   Verifica en Cloudflare Dashboard que la URL sea:"
    echo "   http://app:80  (NO localhost:3001)"
else
    echo "   ⚠️  Respuesta inesperada"
fi

echo ""
echo "4. Configuración actual del tunnel:"
docker compose logs tunnel --tail 5 | grep "service" | tail -1

echo ""
echo "=========================================="
