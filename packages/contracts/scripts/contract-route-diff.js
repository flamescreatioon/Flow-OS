/**
 * Compares the OpenAPI contract in openapi/v1.yaml against the routes the
 * Laravel API actually registers, so that contract drift is caught instead of
 * being discovered by a consumer.
 *
 * Usage:
 *   node scripts/contract-route-diff.js [path-to-route-list.json]
 *
 * With no argument the route list is generated from ../../apps/api. Exit code
 * is 1 when the contract and the implementation disagree.
 */
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const YAML = require('yaml');

const API_DIR = path.resolve(__dirname, '..', '..', '..', 'apps', 'api');
const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

function resolvePhp() {
  const candidates = [process.env.PHP_BINARY, 'php', 'php.exe'].filter(Boolean);

  for (const candidate of candidates) {
    try {
      execFileSync(candidate, ['-v'], { stdio: 'ignore' });

      return candidate;
    } catch {
      // Try the next candidate.
    }
  }

  return null;
}

function routeList(argument) {
  if (argument) {
    return JSON.parse(fs.readFileSync(argument, 'utf8').replace(/^\uFEFF/, ''));
  }

  const php = resolvePhp();
  if (!php) {
    console.error(
      'Unable to run `php artisan route:list`. Set PHP_BINARY to the PHP executable '
      + 'or pass a route list JSON file as the first argument.'
    );
    process.exit(2);
  }

  const output = execFileSync(php, ['artisan', 'route:list', '--json'], {
    cwd: API_DIR,
    encoding: 'utf8',
    maxBuffer: 32 * 1024 * 1024,
  });

  return JSON.parse(output.replace(/^\uFEFF/, ''));
}

function shape(uri) {
  const index = uri.indexOf('api/v1');
  const scoped = index >= 0 ? uri.slice(index + 6) : uri;

  return scoped.replace(/\{[^}]+\}/g, '{}');
}

const spec = YAML.parse(fs.readFileSync(path.resolve(__dirname, '..', 'openapi', 'v1.yaml'), 'utf8'));
const routes = routeList(process.argv[2]);

const specOperations = new Set();
for (const [route, item] of Object.entries(spec.paths)) {
  for (const method of Object.keys(item)) {
    if (['get', 'post', 'put', 'patch', 'delete'].includes(method)) {
      specOperations.add(`${method.toUpperCase()} ${shape(route)}`);
    }
  }
}

const routeOperations = new Set();
for (const route of routes) {
  if (!route.uri.startsWith('api/v1')) {
    continue;
  }

  for (const method of route.method.split('|')) {
    if (METHODS.includes(method)) {
      routeOperations.add(`${method} ${shape(route.uri)}`);
    }
  }
}

const missingInSpec = [...routeOperations].filter((op) => !specOperations.has(op)).sort();
const missingInRoutes = [...specOperations].filter((op) => !routeOperations.has(op)).sort();

console.log(`Laravel routes: ${routeOperations.size}  |  Contract operations: ${specOperations.size}`);

if (missingInSpec.length === 0 && missingInRoutes.length === 0) {
  console.log('Contract and implementation agree.');

  process.exit(0);
}

console.log(`\nImplemented but absent from the contract (${missingInSpec.length}):`);
missingInSpec.forEach((op) => console.log(`  ${op}`));
console.log(`\nContracted but not implemented (${missingInRoutes.length}):`);
missingInRoutes.forEach((op) => console.log(`  ${op}`));

process.exit(1);
