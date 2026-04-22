<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Виджет ЦБ-курсов</title>
    <!-- TailwindCSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Heroicons -->
    <script src="https://unpkg.com/heroicons@2.0.18/24/outline/index.js" type="module"></script>
    <!-- Chart.js for trends -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Курсы валют ЦБ РФ</h1>
            <p class="text-gray-600">Официальные курсы иностранных валют к рублю</p>
        </div>

        <!-- Loading State -->
        <div id="loading" class="text-center py-16">
            <div class="inline-flex items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
                <span class="ml-4 text-xl text-gray-700">Загрузка курсов валют...</span>
            </div>
        </div>

        <!-- Error State -->
        <div id="error" class="hidden text-center py-16">
            <div class="bg-red-50 border border-red-200 rounded-lg p-8 max-w-md mx-auto">
                <div class="text-red-500 text-6xl mb-4">⚠️</div>
                <h3 class="text-lg font-medium text-red-800 mb-2">Ошибка загрузки</h3>
                <p id="errorText" class="text-red-600 mb-4"></p>
                <button onclick="loadRates()" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-lg transition duration-200 shadow-md">
                    Попробовать снова
                </button>
            </div>
        </div>

        <!-- Main Widget -->
        <div id="widget" class="hidden">
            <!-- Controls -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            Обновлено: <span id="lastUpdate" class="font-medium text-gray-900"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse" id="statusIndicator"></div>
                            <span class="text-sm text-gray-600" id="statusText">Подключено</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button id="refreshBtn" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Обновить
                        </button>
                        <a href="/settings" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 transition duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Настройки
                        </a>
                    </div>
                </div>
            </div>

            <!-- Rates Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="ratesContainer">
                <!-- Currency cards will be inserted here -->
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-sm text-gray-500">
                <p>Данные предоставлены Центральным Банком Российской Федерации</p>
                <p class="mt-1">Курсы установлены на текущий рабочий день</p>
            </div>
        </div>
    </div>

    <script>
        let updateInterval = 60; // По умолчанию 60 секунд
        let updateTimer;

        // Загрузка при старте
        document.addEventListener('DOMContentLoaded', async () => {
            await loadSettings();
            await loadRates();

            // Обработчик кнопки обновления
            document.getElementById('refreshBtn').addEventListener('click', async () => {
                await loadRates();
            });
        });

        // Функция загрузки настроек
        async function loadSettings() {
            try {
                const response = await fetch('/api/v1/settings');
                const data = await response.json();
                updateInterval = data.widget_update_interval;
            } catch (error) {
                console.warn('Не удалось загрузить настройки, используем значения по умолчанию:', error);
            }
        }

        // Функция загрузки курсов
        async function loadRates() {
            showLoading();

            try {
                const response = await fetch('/api/v1/widget/rates');

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (!data.rates || Object.keys(data.rates).length === 0) {
                    throw new Error('Нет данных о курсах валют');
                }

                renderRates(data.rates);
                updateLastUpdateTime();

                // Запуск автоматического обновления
                if (updateTimer) {
                    clearInterval(updateTimer);
                }
                updateTimer = setInterval(loadRates, updateInterval * 1000);

            } catch (error) {
                showError('Ошибка загрузки курсов: ' + error.message);
            }
        }

        // Функция рендера карточек валют
        function renderRates(rates) {
            const container = document.getElementById('ratesContainer');
            container.innerHTML = '';

            Object.entries(rates).forEach(([currencyCode, rateData]) => {
                const card = createCurrencyCard(currencyCode, rateData);
                container.appendChild(card);
            });

            hideLoading();
            showWidget();
        }

        // Функция создания карточки валюты
        function createCurrencyCard(currencyCode, rateData) {
            const card = document.createElement('div');
            card.className = 'bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-lg hover:border-gray-300 transition-all duration-200 group';

            let changeIndicator = '';
            let changeClass = '';
            let changeText = '';
            let changeBgClass = '';
            let trendIcon = '';

            if (rateData.today && rateData.yesterday) {
                const todayValue = parseFloat(rateData.today.value);
                const yesterdayValue = parseFloat(rateData.yesterday.value);
                const change = todayValue - yesterdayValue;
                const changePercent = ((change / yesterdayValue) * 100).toFixed(2);

                if (change > 0) {
                    changeIndicator = '+' + formatCurrency(change);
                    changeText = `+${changePercent}%`;
                    changeClass = 'text-green-600 bg-green-50';
                    changeBgClass = 'bg-green-500';
                    trendIcon = `<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>`;
                } else if (change < 0) {
                    changeIndicator = formatCurrency(change);
                    changeText = `${changePercent}%`;
                    changeClass = 'text-red-600 bg-red-50';
                    changeBgClass = 'bg-red-500';
                    trendIcon = `<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>`;
                } else {
                    changeIndicator = '0.0000';
                    changeText = '0.00%';
                    changeClass = 'text-gray-600 bg-gray-50';
                    changeBgClass = 'bg-gray-400';
                    trendIcon = `<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10 11.414l2.707 2.293a1 1 0 001.414-1.414l-4-4a1 1 0 01-.016-1.405l4-4.111a1 1 0 00-1.414-1.414L10 8.586 7.293 6.293a1 1 0 00-1.414 1.414l4 4.111a1 1 0 01.016 1.405l-4 4z" clip-rule="evenodd"></path>
                    </svg>`;
                }
            }

            const currencyName = rateData.currency ? rateData.currency.name : currencyCode;
            const nominal = rateData.currency ? rateData.currency.nominal : 1;
            const currentValue = rateData.today ? formatCurrency(rateData.today.value) : 'Н/Д';
            const vunitRate = rateData.today ? formatCurrency(rateData.today.vunit_rate) : 'Н/Д';

            card.innerHTML = `
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 ${changeBgClass} rounded-full flex items-center justify-center text-white font-bold text-sm">
                            ${currencyCode}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">${currencyCode}</h3>
                                ${nominal > 1 ? `<span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-full">${nominal} шт</span>` : ''}
                            </div>
                            <p class="text-sm text-gray-600">${currencyName}</p>
                        </div>
                    </div>
                    ${rateData.today && rateData.yesterday ? `
                        <div class="${changeClass} px-3 py-1 rounded-full text-sm font-medium flex items-center space-x-1">
                            ${trendIcon}
                            <span>${changeText}</span>
                        </div>
                    ` : ''}
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">За номинал${nominal > 1 ? ` (${nominal} шт)` : ''}</span>
                        <span class="text-2xl font-bold text-gray-900 font-mono">${currentValue}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">За единицу</span>
                        <span class="text-lg font-semibold text-gray-700 font-mono">${vunitRate}</span>
                    </div>
                    ${rateData.yesterday ? `
                        <div class="pt-3 border-t border-gray-100">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Вчера</span>
                                <span class="font-mono text-gray-700">${formatCurrency(rateData.yesterday.value)}</span>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;

            return card;
        }

        // Функция форматирования валюты
        function formatCurrency(value) {
            return parseFloat(value).toFixed(4);
        }

        // Функция обновления времени последнего обновления
        function updateLastUpdateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('ru-RU');
            document.getElementById('lastUpdate').textContent = `${dateString} ${timeString}`;
        }

        // Функции управления видимостью
        function showLoading() {
            document.getElementById('loading').classList.remove('hidden');
            document.getElementById('error').classList.add('hidden');
            document.getElementById('widget').classList.add('hidden');
        }

        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.remove('hidden');
            document.getElementById('widget').classList.add('hidden');
        }

        function showWidget() {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error').classList.add('hidden');
            document.getElementById('widget').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading').classList.add('hidden');
        }
    </script>
</body>
</html>
