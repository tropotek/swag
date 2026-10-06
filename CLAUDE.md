# Swag

## API changes

Whenever you add, remove or change an API endpoint, parameter, response shape or limit, update `resources/openapi.json` (served at `/api/openapi.json`) in the same change. Agents read that document to learn the API, so it must match the code. Also update `docs/api.md` and `skills/swag/SKILL.md` if they describe the change.
