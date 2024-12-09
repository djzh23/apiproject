<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased">
<div class="relative flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
    <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
        <div class="flex flex-col items-center pt-8 sm:justify-start sm:pt-0">
            <div class="flex items-center">
                <div class="px-4 text-lg text-gray-500 border-r border-gray-400 tracking-wider">
                    404
                </div>
                <div class="ml-4 text-lg text-gray-500 uppercase tracking-wider">
                    Page Not Found
                </div>
            </div>

            <div class="mt-8 space-y-4">
                <p class="text-center text-gray-500">
                    Sorry, the page you are looking for could not be found.
                </p>

                <div class="flex justify-center">
                    <a href="{{ url('/') }}"
                       class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors duration-200">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
