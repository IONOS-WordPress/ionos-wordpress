import { execSync } from 'child_process';

// find the name of the wp-env container
const prefix = execSync('bash -c "basename $(pnpm exec wp-env install-path)"').toString().trim();

export function execTestCLI(command) {
  return execSync(`cat <<EOF | docker exec --interactive ${prefix}-tests-cli-1 sh -
    set -x
    ${command}
EOF`)
    .toString()
    .trim();
}
