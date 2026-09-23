# noteBoard skill for AI assistants

`noteboard/SKILL.md` teaches an AI assistant to post, list, update and delete pages on a
noteBoard site through its REST API. It follows the Agent Skills format, which Claude Code
reads natively.

## Install (Claude Code)

Link it into your personal skills folder so it's available in every project:

    ln -s "$PWD/skills/noteboard" ~/.claude/skills/noteboard

Then give it your site and token, either in `~/.claude/settings.json`:

    { "env": { "NOTEBOARD_URL": "https://your-site.example", "NOTEBOARD_TOKEN": "1|..." } }

or exported from your shell profile, with the token in single quotes because it contains `|`:

    export NOTEBOARD_TOKEN='1|...'

Create the token on the site's **API tokens** page,
and revoke it there if it leaks.

Ask things like "put this on my noteBoard" or "update my noteBoard page about X".

## Other assistants

Assistants that don't read skills can still use it. Paste the contents of `SKILL.md` into
their instructions and give them the URL and token.
