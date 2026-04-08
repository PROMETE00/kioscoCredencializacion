#!/bin/bash

echo "================================================================="
echo "DIAGNÓSTICO COMPLETO DEL TUNNEL"
echo "================================================================="
echo ""

echo "1. Estado de contenedores:"
docker compose ps | grep -E "NAME|kiosco"
echo ""

echo "2. IP del app:"
docker inspect kiosco_app --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'
echo ""

echo "3. Configuración actual del tunnel:"
docker compose logs tunnel --tail 50 | grep "Updated to new" | tail -1
echo ""

echo "4. Test interno (desde host a app):"
curl -sI http://localhost:3001 | head -1
echo ""

echo "5. Test interno (desde red Docker):"
docker exec kiosco_app curl -sI http://172.18.0.4:80 | head -1
echo ""

echo "6. Test externo (desde internet):"
curl -sI -m 5 https://credencializacion.prome.works | head -1
echo ""

echo "7. Últimas 5 peticiones al app:"
docker logs kiosco_app --tail 100 | grep -E "GET|HEAD|POST" | tail -5
echo ""

echo "8. Tunnel está recibiendo tráfico?"
docker compose logs tunnel --tail 100 | grep -i "request\|proxy\|dial" | tail -5
if [ $? -ne 0 ]; then
    echo "NO - El tunnel NO está recibiendo peticiones HTTP"
fi
echo ""

echo "================================================================="
echo "DIAGNÓSTICO:"
echo "================================================================="
echo ""

TUNNEL_CONFIG=$(docker compose logs tunnel --tail 50 | grep "Updated to new" | tail -1)
if echo "$TUNNEL_CONFIG" | grep -q "172.18.0.4:80"; then
    echo "✅ Tunnel tiene configuración correcta"
else
    echo "❌ Tunnel NO tiene configuración correcta"
fi

TUNNEL_RUNNING=$(docker compose ps | grep kiosco_tunnel | grep -c "Up")
if [ "$TUNNEL_RUNNING" -eq 1 ]; then
    echo "✅ Tunnel está corriendo"
else
    echo "❌ Tunnel NO está corriendo"
fi

APP_WORKS=$(curl -sI -m 2 http://localhost:3001 | grep -c "200 OK")
if [ "$APP_WORKS" -eq 1 ]; then
    echo "✅ App funciona localmente"
else
    echo "❌ App NO funciona localmente"
fi

EXTERNAL=$(curl -sI -m 5 https://credencializacion.prome.works 2>&1 | grep -c "200 OK")
if [ "$EXTERNAL" -eq 1 ]; then
    echo "✅ Sitio funciona externamente"
else
    echo "❌ Sitio NO funciona externamente (502)"
fi

echo ""
echo "================================================================="
echo "CONCLUSIÓN:"
echo ""
echo "Si todo está ✅ EXCEPTO el acceso externo, el problema está"
echo "en la configuración de Cloudflare Dashboard (NO en el servidor)."
echo ""
echo "Verifica que en Cloudflare Zero Trust > Networks > Tunnels:"
echo "  1. Solo haya UN tunnel con status HEALTHY"
echo "  2. El Public Hostname esté configurado correctamente"
echo "  3. NO haya reglas de firewall bloqueando"
echo "================================================================="

