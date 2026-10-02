export default function NotFound() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center p-24">
      <h1 className="text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
        404
      </h1>
      <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
        Page not found
      </p>
      <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
        The page you&apos;re looking for doesn&apos;t exist or has been moved.
      </p>
    </main>
  );
}