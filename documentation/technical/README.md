# Project Documentation — technical guides

Developer-facing references for working with the codebase.

## 📚 Available Documentation

- **[testing.md](testing.md)** — Testing workflows, Git hooks, and quality checks
- **[forms-service.md](forms-service.md)** — The frontend Forms service
- **[form-requests.md](form-requests.md)** — Custom FormRequest base class for IDE intellisense
- **[BROADCASTING.md](BROADCASTING.md)** — Real-time broadcasting (Reverb/Echo)
- **[ER/](ER/)** — Entity-relationship diagrams

## 🐳 Docker & environments

All infrastructure documentation lives with the infrastructure itself:
**[docker/README.md](../../docker/README.md)** — environments (dev, testing,
staging, prod), the `mtav` CLI, full technical specification, and gotchas.

## 📝 Notes

When you add new docs, link them from this index. The degree thesis
(`documentation/thesis/`) is the authoritative long-form description of the
system; the code is the source of truth for implementation details.
