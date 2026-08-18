---
# u3j8
title: Replace scripts/stretch.sh shim with the real script
status: draft
type: task
created_at: 2026-08-18T08:46:02Z
updated_at: 2026-08-18T08:46:02Z
---

scripts/stretch.sh is currently a wrapper/shim that, on demand, fetches the real
implementation (_stretch.sh) from the private repository
git@github.com:IONOS-WordPress/ionos-wordpress-private.git (path scripts/_stretch.sh,
branch main) via a shallow git fetch + git show, caches it locally, and execs it with
all arguments. See scripts/stretch.sh for the current shim.

The split into a private repo exists because this monorepo is hosted on GitHub, and the
real stretch.sh script's contents (likely including infrastructure/deployment details
not meant to be public) can't be committed there directly.

## NOTICE: blocked on GitLab migration

Do NOT implement this until the ionos-wordpress repo has been finally moved to GitLab.
Once the repo lives on GitLab (presumably a private/internal instance where the
confidentiality concern no longer requires a separate private-repo split), the real
script can be committed directly into this repo (e.g. as scripts/_stretch.sh or
scripts/stretch.sh itself), and the on-demand git-fetch-from-private-repo shim can be
deleted entirely.

## Task

- [ ] Confirm the ionos-wordpress repo has moved to GitLab
- [ ] Pull the current contents of scripts/_stretch.sh from
      git@github.com:IONOS-WordPress/ionos-wordpress-private.git (branch main)
- [ ] Commit it directly into this repo, replacing the shim in scripts/stretch.sh
- [ ] Remove the private-repo fetch/cache logic (download_script, the stretch-remote
      git remote, the --update flag) entirely
- [ ] Update package.json's "stretch" script entry if the script is renamed/moved
- [ ] Decide what happens to the now-unused ionos-wordpress-private repository
      (archive it? keep it for other unrelated content?)
- [ ] Update any docs referencing the private-repo fetch mechanism
