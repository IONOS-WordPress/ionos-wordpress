# Caveats

When updating docker images you need to give the devlopment system that the image needs to be rebuild.

This can be done by simply increasing the version number in the `package.json` of the docker image workspace package.

If you do so and your colleagues do a `git pull` the follow-up `pnpm build` command will also rebuild the docker image.

> [!TIP] If you skip increasing the version number you need to notify your team mates to do a `pnpm build --force` to force rebuildinf the docker images oin their local machine.
