---
title: AI assistants
nav_order: 5
---

# Connecting an AI assistant

Any assistant that can make HTTP requests can post to noteBoard with an API token. For
Claude Code, the repository ships a ready-made skill.

## Claude Code skill

`skills/noteboard/SKILL.md` teaches Claude Code how to use the [REST API](api.md). With it,
Claude can post a page, find an existing one, update it and (after asking) delete it.

### Install

1. Create a token on your site's **API tokens** page.
2. Link the skill into your personal skills folder so it works in every project:

   ```bash
   ln -s "$PWD/skills/noteboard" ~/.claude/skills/noteboard
   ```

   Run this from the repository root. Using a symlink means `git pull` also updates the skill.
   If you don't have the repository, copy the `skills/noteboard` folder to
   `~/.claude/skills/noteboard` instead.
3. Give Claude Code your site and token. Add them to `~/.claude/settings.json`:

   ```json
   {
     "env": {
       "NOTEBOARD_URL": "https://your-site.example",
       "NOTEBOARD_TOKEN": "1|your-token"
     }
   }
   ```

   Or export both from your shell profile. **Quote the token.** Tokens contain `|`, which
   the shell treats as a pipe:

   ```bash
   export NOTEBOARD_URL='https://your-site.example'
   export NOTEBOARD_TOKEN='1|your-token'
   ```

   Use the URL without a trailing slash.
4. Start a new Claude Code session.

### Use

Ask in plain words:

- "Put a summary of this conversation on my noteBoard."
- "Save these steps to noteBoard as 'Router reset'."
- "Update my noteBoard page about the tap repair: add a step for the washer."
- "What's on my noteBoard from this week?"

Claude replies with the page's URL. Before deleting a page or replacing its whole body, it
asks you first.

### Security

- The token can create, edit and delete **your** pages. Keep it out of shared or committed
  files. `~/.claude/settings.json` is per-user and isn't part of any repository.
- The skill tells Claude never to print the token or write it into files.
- If a token leaks, revoke it on the **API tokens** page and create a new one.

## Other agent harnesses

The skill is plain Markdown in the Agent Skills format, and its commands need only `curl`
and `jq`. Any harness that can run shell commands can use it.

| Harness | Install |
|---|---|
| Claude Code | `ln -s "$PWD/skills/noteboard" ~/.claude/skills/noteboard` (above) |
| Codex, Copilot CLI, Gemini CLI | These read the cross-tool folder `~/.agents/skills/`: `mkdir -p ~/.agents/skills && ln -s "$PWD/skills/noteboard" ~/.agents/skills/noteboard` |
| Any other shell-capable agent | Paste the contents of `skills/noteboard/SKILL.md` into its instructions or rules file |

Every harness also needs `NOTEBOARD_URL` and `NOTEBOARD_TOKEN` in its environment
(single-quote the token in shell profiles), plus `curl` and `jq` on the machine.

## Other assistants

- **Chat-only apps** (the claude.ai website, ChatGPT mobile) can't run commands or hold a
  token, so the skill doesn't work there yet. They'll need an MCP connector or a ChatGPT
  Action backed by an OpenAPI description of the API.
- **Assistants with custom instructions** (ChatGPT custom GPTs, other agent frameworks):
  paste the body of `skills/noteboard/SKILL.md` into the instructions, and supply the URL
  and token through whatever secret mechanism the platform offers.
- **Assistants that can't make HTTP calls:** ask them for the Markdown, then post it yourself
  with the `curl` example in the [REST API](api.md) guide.
