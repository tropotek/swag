# Swag skill for AI agents

`swag/SKILL.md` teaches an AI agent to post, list, update and delete pages on a
Swag site through its REST API. It's plain Markdown in the open Agent Skills format,
and its commands need only `curl` and `jq`, so it isn't tied to any one agent.

## Install

Link it where your agent looks for skills, from the repository root:

    mkdir -p ~/.agents/skills && ln -s "$PWD/skills/swag" ~/.agents/skills/swag   # e.g. Codex, Copilot CLI, Gemini CLI
    ln -s "$PWD/skills/swag" ~/.claude/skills/swag                                # Claude Code

For other agents, paste `SKILL.md` into the agent's instructions.

Then give the agent two environment variables, `SWAG_URL` and `SWAG_TOKEN`.
In a shell profile, single-quote the token, because it contains `|`:

    export SWAG_URL='https://your-site.example'
    export SWAG_TOKEN='1|...'

Create the token on the site's **API tokens** page, and revoke it there if it leaks.

Full guide: [docs/ai-assistants.md](../docs/ai-assistants.md).
