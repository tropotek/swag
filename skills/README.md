# noteBoard skill for AI agents

`noteboard/SKILL.md` teaches an AI agent to post, list, update and delete pages on a
noteBoard site through its REST API. It's plain Markdown in the open Agent Skills format,
and its commands need only `curl` and `jq`, so it isn't tied to any one agent.

## Install

Link it where your agent looks for skills, from the repository root:

    mkdir -p ~/.agents/skills && ln -s "$PWD/skills/noteboard" ~/.agents/skills/noteboard   # e.g. Codex, Copilot CLI, Gemini CLI
    ln -s "$PWD/skills/noteboard" ~/.claude/skills/noteboard                                # Claude Code

For other agents, paste `SKILL.md` into the agent's instructions.

Then give the agent two environment variables, `NOTEBOARD_URL` and `NOTEBOARD_TOKEN`.
In a shell profile, single-quote the token, because it contains `|`:

    export NOTEBOARD_URL='https://your-site.example'
    export NOTEBOARD_TOKEN='1|...'

Create the token on the site's **API tokens** page, and revoke it there if it leaks.

Full guide: [docs/ai-assistants.md](../docs/ai-assistants.md).
