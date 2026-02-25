# You-system — Documentação do Projeto e Melhorias

## 1) Visão geral
O **You-system** é um resolvedor interno para obter URL HLS de vídeos/lives do YouTube a partir de `videoId`.

Fluxo principal:
1. Recebe `id`
2. Consulta página do YouTube
3. Extrai `hlsManifestUrl`
4. Retorna:
   - redirect (padrão), ou
   - JSON (`format=json`)

---

## 2) Estrutura atual

```text
you-system/
├─ public/
│  └─ index.php                  # endpoint principal
├─ src/
│  ├─ YouTubeLiveResolver.php    # resolução da URL HLS
│  ├─ HttpResponder.php          # respostas JSON/redirect
│  ├─ AppLogger.php              # logging estruturado
│  ├─ FileCache.php              # cache por arquivo com TTL
│  └─ RateLimiter.php            # limite por IP
├─ config/
│  └─ config.php                 # configurações centrais
├─ deploy/
│  ├─ install-ubuntu-22.04.sh    # instalação Ubuntu + Nginx + PHP-FPM
│  ├─ update.sh                  # atualização do serviço
│  └─ healthcheck.sh             # validação pós deploy
├─ docs/
│  └─ DOCUMENTACAO_PROJETO_E_MELHORIAS.md
├─ You.php                       # compatibilidade com script legado
└─ README.md
```

---

## 3) Endpoints

### 3.1 Health
`GET /?health=1`

Resposta exemplo:
```json
{
  "ok": true,
  "service": "you-system",
  "time": "2026-02-25T09:00:00Z"
}
```

### 3.2 Resolver HLS (redirect)
`GET /?id=WkhCfPPgqWc`

### 3.3 Resolver HLS (JSON)
`GET /?id=WkhCfPPgqWc&format=json`

Resposta exemplo:
```json
{
  "ok": true,
  "videoId": "WkhCfPPgqWc",
  "hls": "https://....m3u8",
  "cacheHit": false
}
```

### 3.4 Com token (quando habilitado)
`GET /?id=WkhCfPPgqWc&format=json&token=SEU_TOKEN`

Ou header:
`X-API-Token: SEU_TOKEN`

---

## 4) Melhorias já aplicadas

## ✅ Organização e manutenção
- Refatoração para arquitetura em camadas (`public`, `src`, `config`)
- Compatibilidade mantida com `You.php` legado
- README e scripts de deploy/documentação

## ✅ Segurança básica
- Validação forte de `videoId`
- Token de API opcional (`YOU_SYSTEM_TOKEN`)
- Rate limit por IP

## ✅ Resiliência
- Cache local com TTL para reduzir chamadas repetidas
- Tratamento de erros padronizado
- Health endpoint

## ✅ Operação
- Logs estruturados JSON em `logs/`
- Instalação/atualização automatizada para Ubuntu 22.04

---

## 5) Configurações (`config/config.php`)

Campos principais:
- `userAgent`
- `timeoutSeconds`
- `allowOrigin`
- `apiToken` (lido de `YOU_SYSTEM_TOKEN`)
- `rateLimitWindowSeconds`
- `rateLimitMaxRequests`
- `cacheDir`
- `cacheTtlSeconds`
- `logDir`

---

## 6) Deploy Ubuntu 22.04

## Instalação
```bash
chmod +x deploy/*.sh
./deploy/install-ubuntu-22.04.sh
```

## Atualização
```bash
./deploy/update.sh
```

## Healthcheck
```bash
./deploy/healthcheck.sh
```

---

## 7) Próximas melhorias recomendadas

1. **Whitelist de IP** (interno)
2. **Autenticação por header obrigatório** (desabilitar query token)
3. **Métricas Prometheus/Grafana**
4. **Retry/fallback de parser** para mudanças de HTML
5. **Docker Compose** para padronizar ambiente
6. **TLS automático** + renovação certbot

---

## 8) Limitações conhecidas

- O método depende de parsing de HTML do YouTube.
- Mudanças no front do YouTube podem exigir ajuste do regex/parser.
- É recomendado para ambiente interno/laboratório; para escala 24/7, usar serviço dedicado com monitoramento contínuo.

---

## 9) Resumo executivo

O projeto saiu de script simples para um serviço interno com:
- arquitetura organizada,
- segurança mínima,
- deploy automatizado,
- observabilidade básica,
- e base pronta para evoluir para operação mais robusta.
