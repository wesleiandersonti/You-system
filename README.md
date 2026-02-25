# You-system (YouTube Live Resolver)

Versão organizada/profissional do script `You.php` para uso interno.

## O que faz
- Recebe `id` (videoId do YouTube)
- Tenta extrair `hlsManifestUrl`
- Pode **redirecionar** para o `.m3u8` ou retornar **JSON**

## Melhorias incluídas
- Estrutura por camadas (`public/`, `src/`)
- Validação de entrada (`id`)
- Tratamento de erro padronizado
- Resposta JSON opcional (`format=json`)
- Config central em `config/config.php`
- Token de API obrigatório por padrão (`YOU_SYSTEM_TOKEN`)
- Whitelist de IP opcional
- Rate limit por IP
- Cache local com TTL
- Retry com backoff para resolução
- Logs JSON em `logs/` com retenção
- Endpoint `metrics` para operação
- Docker Compose pronto

## Uso rápido

### 1) Servidor local
```bash
php -S 127.0.0.1:8090 -t public
```

### 2) Exemplos
- Health:
  `http://127.0.0.1:8090/?health=1`
- Metrics:
  `http://127.0.0.1:8090/?metrics=1`
- Redirect (padrão):
  `http://127.0.0.1:8090/?id=WkhCfPPgqWc&token=SEU_TOKEN`
- JSON:
  `http://127.0.0.1:8090/?id=WkhCfPPgqWc&format=json&token=SEU_TOKEN`

## Ubuntu 22.04 (instalação)
Scripts prontos em `deploy/`:

```bash
chmod +x deploy/*.sh
./deploy/install-ubuntu-22.04.sh
```

Após instalar:

- Atualizar:
```bash
./deploy/update.sh
```

- Healthcheck:
```bash
./deploy/healthcheck.sh
```

### HTTPS (opcional/recomendado)
```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com
```

## Compatibilidade
`You.php` foi mantido como ponte para o endpoint novo em `public/index.php`.

## Documentação completa
Veja detalhes de arquitetura e melhorias em:
- `docs/DOCUMENTACAO_PROJETO_E_MELHORIAS.md`

## Aviso
Este método depende de parsing de HTML do YouTube (pode quebrar com mudanças do site).
Para produção 24/7, prefira serviço dedicado com monitoramento e fallback.
