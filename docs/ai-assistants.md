# Connecting an AI assistant

Swag isn't tied to any one assistant. Anything that can make an HTTP request with a
Bearer token can post pages: coding agents, agent frameworks, scripts, or a custom GPT.
All it needs is the [REST API](api.md) and a token.

To make this easy, the repository ships a portable **skill**, `skills/swag/SKILL.md`.
It's a plain Markdown file in the open Agent Skills format that tells an agent how to post,
find, update and (after asking) delete pages. Its commands use only `curl` and `jq`.

## What every setup needs

1. **A token.** Create one on your site's **API tokens** page. Use one token per assistant,
   so you can revoke them separately.
2. **Two environment variables** the agent can read:

   | Variable | Value |
   |---|---|
   | `SWAG_URL` | Your site, e.g. `https://notes.example.com` (no trailing slash) |
   | `SWAG_TOKEN` | The token |

   If you set them in a shell profile, **single-quote the token**, because tokens contain `|`:

   ```bash
   export SWAG_URL='https://notes.example.com'
   export SWAG_TOKEN='1|your-token'
   ```

3. **`curl` and `jq`** on the machine the agent runs on. You only need these for the skill;
   direct API integrations can use any HTTP client.

## Installing the skill

Run these from the repository root. A symlink means `git pull` also updates the skill. If you
don't have the repository, copy the `skills/swag` folder instead.

| Agent | Install |
|---|---|
| Agents that read `~/.agents/skills/` (e.g. Codex, Copilot CLI, Gemini CLI) | `mkdir -p ~/.agents/skills && ln -s "$PWD/skills/swag" ~/.agents/skills/swag` |
| Claude Code | `ln -s "$PWD/skills/swag" ~/.claude/skills/swag` |
| Any other agent that can run shell commands | Paste the contents of `SKILL.md` into its instructions or rules file |

Then provide the environment variables in whatever way the agent supports: a shell profile,
the agent's own settings file, or its secrets store. Start a new session afterwards.

### Example: Claude Code

```bash
ln -s "$PWD/skills/swag" ~/.claude/skills/swag
```

Add the variables to `~/.claude/settings.json`, which is per-user and not part of any repository:

```json
{
  "env": {
    "SWAG_URL": "https://notes.example.com",
    "SWAG_TOKEN": "1|your-token"
  }
}
```

Other agents follow the same pattern: put the skill where the agent looks for skills, then
put the two variables where the agent reads its environment.

## Using it

Ask in plain words:

- "Add this to the swag mate."
- "Chuck these steps in the swag as 'Router reset'."
- "Update the tap repair page in the swag: add a step for the washer."
- "What's in the swag from this week?"

The agent replies with the page's URL. Before deleting a page or replacing its whole body,
it asks you first.

## Assistants without a shell

- **Custom instructions plus HTTP tools** (custom GPTs, agent frameworks): give them the
  body of `SKILL.md` as instructions, or wire their HTTP tool directly to the
  [REST API](api.md). Supply the token through the platform's secret mechanism.
- **Chat-only apps** (assistant websites and phone apps with no tools): these can't make the
  request themselves yet. For now, ask them for the Markdown and post it with the `curl`
  example in the [REST API](api.md) guide. An MCP server or an OpenAPI description (for
  ChatGPT Actions) would let them post directly, and is on the roadmap.

## Security

- A token can create, edit and delete **your** pages, nothing else. Keep it out of shared or
  committed files.
- The skill tells the agent never to print the token or write it into files.
- If a token leaks, revoke it on the **API tokens** page and create a new one.
