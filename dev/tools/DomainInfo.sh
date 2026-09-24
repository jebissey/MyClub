#!/usr/bin/env bash
# ============================================================
#  domain-info.sh — Investigation complète d'un domaine
#  Usage : ./domain-info.sh exemple.fr
# ============================================================

if [[ -z $1 ]]; then
  echo "Usage: $0 <domaine>"
  exit 1
fi

DOMAIN="$1"

sep() { echo -e "\n\e[34m══════ $1 ══════\e[0m"; }

# ── 1. WHOIS — registrar, propriétaire, dates ─────────────
sep "WHOIS — Registrar & propriétaire"
whois "$DOMAIN" | grep -iE \
  'registrar|registrant|owner|name server|created|expir|updated|status|abuse|email'

# ── 2. DNS — Adresses IP (A / AAAA) ───────────────────────
sep "DNS — Adresses IP (A / AAAA)"
dig +short A    "$DOMAIN"
dig +short AAAA "$DOMAIN"

# ── 3. Nameservers ────────────────────────────────────────
sep "Nameservers (NS)"
dig +short NS "$DOMAIN"

# ── 4. Serveur mail (MX) ──────────────────────────────────
sep "Messagerie (MX)"
dig +short MX "$DOMAIN"

# ── 5. Hébergeur via rDNS + ASN (ipinfo.io) ───────────────
sep "Hébergeur (rDNS + ASN)"
IP=$(dig +short A "$DOMAIN" | head -1)
echo "IP principale : $IP"
host "$IP" 2>/dev/null || echo "(pas de rDNS)"
curl -s "https://ipinfo.io/$IP/json" | \
  python3 -c "
import sys, json
d = json.load(sys.stdin)
print(f\"  Org  : {d.get('org','?')}\")
print(f\"  Pays : {d.get('country','?')}  Ville : {d.get('city','?')}\")
print(f\"  ASN  : {d.get('asn', d.get('org','?'))}\")
"

# ── 6. Certificat TLS / SSL ───────────────────────────────
sep "Certificat TLS / SSL"
echo | openssl s_client \
  -connect "${DOMAIN}:443" \
  -servername "$DOMAIN" 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates 2>/dev/null \
  || echo "Pas de certificat TLS trouvé."

# ── 7. SPF / DMARC ────────────────────────────────────────
sep "SPF / DMARC (anti-spam)"
echo -n "SPF   : "
dig +short TXT "$DOMAIN"       | grep -i spf || echo "(aucun)"
echo -n "DMARC : "
dig +short TXT "_dmarc.$DOMAIN" || echo "(aucun)"

# ── 8. Redirections HTTP ──────────────────────────────────
sep "Redirections HTTP"
curl -sI --max-redirs 5 -L "https://$DOMAIN" 2>/dev/null \
  | grep -iE "HTTP/|location:|server:" \
  || echo "Pas de réponse HTTP."

# ── 9. Technologies détectées (headers) ───────────────────
sep "Technologies détectées"
curl -sI "https://$DOMAIN" 2>/dev/null \
  | grep -iE "x-powered-by|x-generator|server:|via:|x-cache" \
  || echo "(aucun header technologique détecté)"

sep "✓ Analyse terminée : $DOMAIN"