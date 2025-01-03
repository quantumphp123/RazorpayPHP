<?php
include VIEW_PATH . 'layouts/layout.php';
?>

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">

        <div class="bg-gradient-to-r from-red-300 to-red-700 p-6">
            <div class="flex justify-center mt-2">
                <div class="flex justify-center items-center w-24 h-24 bg-gray-100 rounded-full animate-bounce">
                    <div
                        class="relative w-20 h-20 bg-white text-red-400 flex justify-center items-center rounded-full shadow-lg border-red-600 border-2">
                        <!-- Triangle -->
                        <div
                            class="absolute w-0 h-0 border-l-[16px] border-r-[16px] border-t-[28px] border-t-transparent border-l-yellow-500 border-r-yellow-500">
                        </div>

                        <!-- Exclamation mark -->
                        <div class="absolute text-3xl font-bold">
                            <div class="w-2 h-12 bg-red-600 mx-auto mb-2 rounded-md"></div>
                            <div class="w-2 h-2 bg-red-600 rounded-full mx-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
            <h1 class="text-2xl font-bold text-white text-center mt-2 animate-fade-in">
                Something Went Wrong
            </h1>
        </div>

        <div class="p-8">
            <div class="space-y-6">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 animate-slide-up">
                    <div class="grid grid-cols-1 gap-4 text-center">

                        <div class="animate-fade-in">
                            <p class="text-3xl font-bold text-red-600 mb-2"><?php echo htmlspecialchars($errorCode); ?>
                            </p>

                            <!-- Error Title -->
                            <p class="text-lg text-gray-700"> <?php echo htmlspecialchars($errorTitle); ?></p>
                        </div>

                        <!-- Error Description (Optional) -->
                        <?php if (!empty($errorDescription)): ?>
                            <div class="animate-fade-in delay-300">
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($errorDescription); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="flex items-center justify-center space-x-4 animate-slide-up delay-500">
                    <a href="/" class="inline-flex items-center px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300
                        rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                        focus:ring-red-500 transition-all duration-300 hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Go Back
                    </a>
                    <a href="https://quantumitinnovation.com"
                        class="inline-flex items-center px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-300 hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="4"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Home
                    </a>
                </div>


                <div class="text-center animate-fade-in delay-700">
                    <p class="text-sm text-gray-500">
                        Need assistance? Contact our support team at
                        <a href="mailto:support@company.com" class="text-red-600 hover:text-red-700 ml-1">
                            <?php echo $GLOBALS['config']->get('app')['support'] ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include VIEW_PATH . 'layouts/footer.php';
?>
