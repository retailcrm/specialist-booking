# AGENTS.md

<!-- embed-ui-agents:@retailcrm/embed-ui-v1-endpoint:start -->
## @retailcrm/embed-ui-v1-endpoint

When working with `@retailcrm/embed-ui-v1-endpoint` in this project:

1. Read `./node_modules/@retailcrm/embed-ui-v1-endpoint/README.md`.
2. Then read the relevant guide from `./node_modules/@retailcrm/embed-ui-v1-endpoint/docs/README.md`.
3. Use documented public entrypoints instead of package internals:
   - `@retailcrm/embed-ui-v1-endpoint/remote`
   - `@retailcrm/embed-ui-v1-endpoint/common/targets`
4. Do not import from `@retailcrm/embed-ui-v1-endpoint/dist/*`, source files, or repository-only paths.
5. When the task involves widget targets, target placement, target contexts, target metadata, or choosing a target, use the package MCP server if it is available.
6. First read `embed-ui-v1-endpoint://targets` to discover available target profiles.
7. Then read the relevant `embed-ui-v1-endpoint://targets/<encoded-target>` resource before answering or changing code related to that target.
8. A project `.mcp.json` may require restarting or reconnecting the AI client before MCP resources appear in the current session.
9. If MCP resources are not available, use the generated YAML profiles from `./node_modules/@retailcrm/embed-ui-v1-endpoint/docs/targets/*.yml` as the fallback source.
10. Prefer target profiles over guessing target placement, contexts, or semantic intent from names alone.

Suggested MCP stdio server configuration:

```json
{
  "command": "${CLAUDE_PROJECT_DIR:-.}/node_modules/.bin/embed-ui-v1-endpoint-mcp"
}
```
<!-- embed-ui-agents:@retailcrm/embed-ui-v1-endpoint:end -->

<!-- embed-ui-agents:start -->
## @retailcrm/embed-ui-v1-components

When working with `@retailcrm/embed-ui-v1-components` in this project:

1. Read `./node_modules/@retailcrm/embed-ui-v1-components/README.md`.
2. Then read `./node_modules/@retailcrm/embed-ui-v1-components/AGENTS.md`.
3. Then read `./node_modules/@retailcrm/embed-ui-v1-components/docs/AI.md`.
4. Then read `./node_modules/@retailcrm/embed-ui-v1-components/docs/COMPONENTS.md`.
5. Then read `./node_modules/@retailcrm/embed-ui-v1-components/docs/PROFILES.md`.
6. Then open relevant component profiles from `./node_modules/@retailcrm/embed-ui-v1-components/docs/profiles/components/*.yml`.
7. For complete pages, modals, sidebars, filters, tables, or settings layouts, open the relevant
   page profile from `./node_modules/@retailcrm/embed-ui-v1-components/docs/profiles/pages/*.yml`.
8. Prefer those docs and profiles over guessing from internal implementation files.
9. Import only from documented public entrypoints:
   - `@retailcrm/embed-ui-v1-components/remote`
   - `@retailcrm/embed-ui-v1-components/host`
   - `@retailcrm/embed-ui-v1-components/assets/...`
10. Prefer `@retailcrm/embed-ui-v1-components/remote` for extension UI code.
11. Do not import from package-internal files such as `dist/*`, repository-only paths, or source internals.

## Suggested Reading Order

1. `README.md`
2. `AGENTS.md`
3. `docs/AI.md`
4. `docs/COMPONENTS.md`
5. `docs/PROFILES.md`
6. The relevant component profile from `docs/profiles/components/*.yml`
7. The relevant page profile from `docs/profiles/pages/*.yml` for full-screen or overlay composition
8. `docs/FORMAT.md` if you need to understand profile structure
9. Public type declarations only when no profile exists yet
<!-- embed-ui-agents:end -->

<!-- embed-ui-agents:@retailcrm/embed-ui-v1-contexts:start -->
## @retailcrm/embed-ui-v1-contexts

When working with `@retailcrm/embed-ui-v1-contexts` in this project:

1. Read `./node_modules/@retailcrm/embed-ui-v1-contexts/README.md`.
2. Then read `./node_modules/@retailcrm/embed-ui-v1-contexts/docs/ru/CONCEPT.md`.
3. Then read `./node_modules/@retailcrm/embed-ui-v1-contexts/docs/ru/CUSTOM.md` if custom fields or custom dictionaries are involved.
4. Use documented public entrypoints instead of package internals:
   - `@retailcrm/embed-ui-v1-contexts/remote`
   - `@retailcrm/embed-ui-v1-contexts/remote/settings`
   - `@retailcrm/embed-ui-v1-contexts/remote/user/current`
   - `@retailcrm/embed-ui-v1-contexts/remote/order/card`
   - `@retailcrm/embed-ui-v1-contexts/remote/order/card-settings`
   - `@retailcrm/embed-ui-v1-contexts/remote/customer/card`
   - `@retailcrm/embed-ui-v1-contexts/remote/customer/card-phone`
   - `@retailcrm/embed-ui-v1-contexts/remote/custom`
   - `@retailcrm/embed-ui-v1-contexts/host`
5. Do not import from `@retailcrm/embed-ui-v1-contexts/dist/*`, source files, or repository-only paths.
6. When the task involves available contexts, context fields, actions, action scopes, custom contexts, custom fields, or dictionaries, use the package MCP server if it is available.
7. First read `embed-ui-v1-contexts://contexts`, `embed-ui-v1-contexts://actions`, or `embed-ui-v1-contexts://custom-contexts` to discover available profiles.
8. Then read the relevant resource before answering or changing code:
   - `embed-ui-v1-contexts://contexts/<encoded-context>`
   - `embed-ui-v1-contexts://actions/<encoded-scope>`
   - `embed-ui-v1-contexts://custom-contexts/<encoded-entity>`
9. A project `.mcp.json` may require restarting or reconnecting the AI client before MCP resources appear in the current session.
10. If MCP resources are not available, use generated YAML profiles from `./node_modules/@retailcrm/embed-ui-v1-contexts/docs/contexts/*.yml`, `./node_modules/@retailcrm/embed-ui-v1-contexts/docs/actions/*.yml`, and `./node_modules/@retailcrm/embed-ui-v1-contexts/docs/custom-contexts/*.yml` as fallback sources.
11. Prefer generated profiles over guessing context shape, field names, action scopes, or semantic intent from names alone.

Suggested MCP stdio server configuration:

```json
{
  "command": "${CLAUDE_PROJECT_DIR:-.}/node_modules/.bin/embed-ui-v1-contexts-mcp"
}
```
<!-- embed-ui-agents:@retailcrm/embed-ui-v1-contexts:end -->
