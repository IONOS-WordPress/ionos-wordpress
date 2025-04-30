import { execSync } from 'child_process';

const prefix = execSync('bash -c "basename $(pnpm exec wp-env install-path)"').toString().trim();
console.log('prefix', prefix);

export function execTestCLI(command) {
  execSync(`cat <<EOF | docker exec --interactive ${prefix}-tests-cli-1 sh -
    set -x
    ${command}
EOF`);
}
