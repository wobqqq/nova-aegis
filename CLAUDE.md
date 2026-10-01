# CLAUDE.md

@AGENTS.md

## Claude Code

- Skills in `.claude/skills/`: `aegis-security` (read it for any change to input, output, access or the scanners), `package-upgrades` (anything that reaches an installed application), `package-testing`, `nova-development`, `nova-component-testing`, `testing-best-practices`, `laravel-best-practices`.
- A changed PHP file is formatted by the `PostToolUse` hook in `.claude/settings.json`; still run `make ready` before you say a change is done, and report its result.
- The five modules live in sibling repositories. A change to the contract listed in AGENTS.md is checked against each of them.
- Never push to `main`: work on a branch and open a pull request (see *Git workflow* in AGENTS.md). Write everything in English.
