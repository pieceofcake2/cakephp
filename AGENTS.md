# AGENTS.md

Rules for AI agents (and humans) working on pieceofcake2/cakephp, the community-maintained CakePHP 2.x fork.
The organization-wide rules for commit messages and releases also apply.

## Documentation pull request

The book lives in a separate repository, [pieceofcake2/docs](https://github.com/pieceofcake2/docs).

- When you open a pull request here, open the matching documentation pull request on pieceofcake2/docs at the
  same time. That applies to new features, behavior changes, deprecations, new configuration and security
  fixes.
  - The docs pull request updates the English and the Japanese pages (`docs/en/`, `docs/ja/`).
  - It also updates the changes listed by version: the "Migrating from CakePHP 2.10" appendix and, for a
    feature, Getting Started.
- Link the two pull requests to each other in their descriptions, e.g. "Docs: pieceofcake2/docs#123" and
  "Code: pieceofcake2/cakephp#456".
- Merge the docs pull request together with the code pull request, not before it.
- A pull request that does not change anything users can see (CI, internal refactoring, tests only) needs no
  docs pull request. Say so in its description.

## Other rules

- Backward compatibility: no native parameter, return or property types in `src/`, and no changes to public
  or protected signatures. Types go in docblocks (PHPStan level 5). Test methods in `tests/TestCase/` declare
  native return types.
- Every change needs tests. Add an entry under "Unreleased" in `CHANGELOG.md`, and in `UPGRADE.md` when it
  may affect applications.
- Never open pull requests, issues or comments on upstream repositories (cakephp/*).
