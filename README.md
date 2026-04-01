# ONOXIA - AI Chatbot for Joomla

AI-powered chat widget for Joomla 4, 5, and 6. Connects your website to the [ONOXIA](https://onoxia.nz) chatbot platform with RAG knowledge base, live chat, and GDPR compliance.

## Features

- **Auto Knowledge Import** — Joomla articles are automatically synced to the bot's knowledge base
- **llms.txt + Sitemap Import** — Bot instantly knows your entire website structure
- **Context-Sensitive RAG** — Tags and menu item restrictions for targeted responses
- **Live Chat** — Handover to human support agents
- **GDPR by Design** — No cookies, IP hashing, EU-based AI providers
- **15 Languages** — EN, DE, FR, ES, IT, PT, NL, PL, JA, KO, ZH, TH, MS, ID, VI
- **Joomla 4/5/6** — PSR-4 namespaces, typed events, no legacy code

## Requirements

- Joomla 4.0+ / 5.0+ / 6.0+
- PHP 8.1+
- An [ONOXIA](https://onoxia.nz) account with an API token

## Installation

1. Download the plugin ZIP from [Releases](https://github.com/ocenox/onoxia-joomla/releases)
2. Install via **System > Install > Extensions** in Joomla admin
3. Enable the plugin at **System > Plugins > ONOXIA**
4. Enter your API token (create one at [onoxia.nz/app/api-tokens](https://onoxia.nz/app/api-tokens))

## Configuration

| Setting | Description |
|---|---|
| **API Token** | Your ONOXIA API token (required) |
| **Context Tags** | Comma-separated tags for context-sensitive RAG |
| **Sync Articles** | Auto-sync articles as RAG sources on save |
| **Import llms.txt** | Daily import of your site's llms.txt |
| **Sitemap Navigation** | Daily sitemap import so the bot can refer visitors to pages |
| **Show on Menu Items** | Restrict widget to specific pages (empty = all pages) |

## Links

- [ONOXIA Website](https://onoxia.nz)
- [Dashboard](https://onoxia.nz/app)
- [API Documentation](https://onoxia.nz/docs/api)
- [OCENOX LTD](https://ocenox.com)

## License

GPL-2.0-or-later — see [LICENSE](LICENSE)

---

Developed by [OCENOX LTD](https://ocenox.com) — Made in New Zealand
