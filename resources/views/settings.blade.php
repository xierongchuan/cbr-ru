<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Настройки Виджета ЦБ-курсов</title>
        <!-- TailwindCSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <!-- Heroicons -->
        <script src="https://unpkg.com/heroicons@2.0.18/24/outline/index.js" type="module"></script>
        <style>
            .slider::-webkit-slider-thumb {
                appearance: none;
                height: 20px;
                width: 20px;
                border-radius: 50%;
                background: #3b82f6;
                cursor: pointer;
                border: 2px solid #ffffff;
                box-shadow: 0 0 0 2px #3b82f6;
            }
            .slider::-moz-range-thumb {
                height: 20px;
                width: 20px;
                border-radius: 50%;
                background: #3b82f6;
                cursor: pointer;
                border: 2px solid #ffffff;
                box-shadow: 0 0 0 2px #3b82f6;
            }
        </style>
    </head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Настройки виджета</h1>
            <p class="text-gray-600">Настройте валюты и параметры отображения</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form id="settingsForm" class="p-8 space-y-8">
                <!-- Подгружаемые валюты -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Подгружаемые валюты</h3>
                            <p class="text-sm text-gray-600">Валюты, которые будут загружаться из ЦБ РФ</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="selectAllCbr"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <label for="selectAllCbr" class="text-sm font-medium text-gray-700">Выбрать все</label>
                            </div>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="currencySearchCbr"
                                    placeholder="Поиск валют..."
                                    class="w-48 pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="cbrCurrencies" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto">
                        </div>
                    </div>
                </div>

                <!-- Валюты в виджете -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Валюты в виджете</h3>
                            <p class="text-sm text-gray-600">Выберите валюты для отображения в виджете.</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <input
                                    type="checkbox"
                                    id="selectAllWidget"
                                    class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                                >
                                <label for="selectAllWidget" class="text-sm font-medium text-gray-700">Выбрать все</label>
                            </div>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="currencySearchWidget"
                                    placeholder="Поиск валют..."
                                    class="w-48 pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                >
                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div id="widgetCurrencies" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 max-h-64 overflow-y-auto">
                        </div>
                    </div>
                </div>

                <!-- Интервал обновления -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Интервал обновления</h3>
                            <p class="text-sm text-gray-600">Как часто виджет будет обновлять курсы валют</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center space-x-4">
                            <input
                                type="range"
                                id="updateInterval"
                                name="widget_update_interval"
                                min="10"
                                max="3600"
                                step="10"
                                value="60"
                                class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer slider"
                            >
                            <div class="flex items-center space-x-2 min-w-0">
                                <input
                                    type="number"
                                    id="updateIntervalNumber"
                                    name="widget_update_interval_number"
                                    min="10"
                                    max="3600"
                                    class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    placeholder="60"
                                >
                                <span class="text-sm text-gray-600 whitespace-nowrap">секунд</span>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            Рекомендуется: 60-300 секунд для оптимальной производительности
                        </div>
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a href="/widget" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        К виджету
                    </a>

                    <div class="flex space-x-3">
                        <button
                            type="button"
                            id="resetButton"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200"
                        >
                            Сбросить
                        </button>
                        <button
                            type="submit"
                            id="saveButton"
                            class="inline-flex items-center px-6 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 disabled:opacity-50"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Сохранить настройки
                        </button>
                    </div>
                </div>
            </form>

            <!-- Сообщения -->
            <div id="message" class="hidden mx-8 mb-8">
                <div id="messageContent" class="rounded-lg p-4 flex items-center space-x-3"></div>
            </div>
        </div>
    </div>

    <script>
        // Глобальные переменные
        let availableCurrencies = [];
        let currentSettings = {};

        // Загрузка текущих настроек при загрузке страницы
        document.addEventListener('DOMContentLoaded', () => {
            loadSettings();
            setupEventListeners();
        });

        // Настройка обработчиков событий
        function setupEventListeners() {
            // Связывание слайдера и числового поля
            const slider = document.getElementById('updateInterval');
            const numberInput = document.getElementById('updateIntervalNumber');

            slider.addEventListener('input', (e) => {
                numberInput.value = e.target.value;
            });

            numberInput.addEventListener('input', (e) => {
                const value = Math.max(10, Math.min(3600, parseInt(e.target.value) || 60));
                slider.value = value;
                e.target.value = value;
            });

            numberInput.addEventListener('change', (e) => {
                const value = Math.max(10, Math.min(3600, parseInt(e.target.value) || 60));
                slider.value = value;
                e.target.value = value;
            });

            // Поиск валют для ЦБ
            document.getElementById('currencySearchCbr').addEventListener('input', (e) => {
                filterCurrencies('cbrCurrencies', e.target.value);
            });

            // Выбор всех для ЦБ
            document.getElementById('selectAllCbr').addEventListener('change', (e) => {
                selectAllCurrencies('cbrCurrencies', e.target.checked);
            });

            // Поиск валют для виджета
            document.getElementById('currencySearchWidget').addEventListener('input', (e) => {
                filterCurrencies('widgetCurrencies', e.target.value);
            });

            // Выбор всех для виджета
            document.getElementById('selectAllWidget').addEventListener('change', (e) => {
                selectAllCurrencies('widgetCurrencies', e.target.checked);
            });

            // Сброс настроек
            document.getElementById('resetButton').addEventListener('click', resetSettings);
        }

        // Функция фильтрации валют
        function filterCurrencies(containerId, query) {
            const container = document.getElementById(containerId);
            const items = container.querySelectorAll('[data-currency]');

            items.forEach(item => {
                const currencyData = item.getAttribute('data-currency');
                const isVisible = !query || currencyData.includes(query.toLowerCase().trim());
                item.style.display = isVisible ? '' : 'none';
            });
        }

        // Функция выбора/снятия выбора всех валют
        function selectAllCurrencies(containerId, checked) {
            const container = document.getElementById(containerId);
            const checkboxes = container.querySelectorAll('input[type="checkbox"]');

            checkboxes.forEach(checkbox => {
                if (checkbox.offsetParent !== null) { // Только видимые чекбоксы
                    checkbox.checked = checked;
                }
            });
        }

        // Функция сброса настроек
        function resetSettings() {
            if (confirm('Вы уверены, что хотите сбросить настройки к значениям по умолчанию?')) {
                // Сброс к значениям по умолчанию
                document.getElementById('updateInterval').value = 60;
                document.getElementById('updateIntervalNumber').value = 60;

                // Установка значений по умолчанию
                const defaultCbr = ['USD', 'EUR', 'CNY'];
                const defaultWidget = ['USD', 'EUR'];

                availableCurrencies.forEach(currency => {
                    const cbrCheckbox = document.querySelector(`#cbrCurrencies input[value="${currency.code}"]`);
                    const widgetCheckbox = document.querySelector(`#widgetCurrencies input[value="${currency.code}"]`);

                    if (cbrCheckbox) cbrCheckbox.checked = defaultCbr.includes(currency.code);
                    if (widgetCheckbox) widgetCheckbox.checked = defaultWidget.includes(currency.code);
                });
            }
        }

        // Обработчик формы
        document.getElementById('settingsForm').addEventListener('submit', saveSettings);

        // Функция загрузки настроек
        async function loadSettings() {
            try {
                const response = await fetch('/api/v1/settings');
                const data = await response.json();

                // Сохранение текущих настроек
                currentSettings = data;

                // Установка доступных валют
                availableCurrencies = data.available_currencies || [];

                // Fallback для популярных валют, если список пустой
                if (availableCurrencies.length === 0) {
                    availableCurrencies = [
                        { code: 'USD', name: 'Доллар США' },
                        { code: 'EUR', name: 'Евро' },
                        { code: 'CNY', name: 'Китайский юань' },
                        { code: 'GBP', name: 'Фунт стерлингов' },
                        { code: 'JPY', name: 'Японская йена' },
                        { code: 'CHF', name: 'Швейцарский франк' },
                        { code: 'TRY', name: 'Турецкая лира' },
                        { code: 'KZT', name: 'Казахстанский тенге' }
                    ];
                    showMessage('Используется демо-список валют. Для полного списка дождитесь синхронизации данных.', 'info');
                }

                // Установка интервала
                document.getElementById('updateInterval').value = data.widget_update_interval;
                document.getElementById('updateIntervalNumber').value = data.widget_update_interval;

                // Рендер чекбоксов для валют ЦБ
                renderCurrencyCheckboxes('cbrCurrencies', data.cbr_fetch_currencies || [], 'cbr');
                // Рендер чекбоксов для валют виджета
                renderCurrencyCheckboxes('widgetCurrencies', data.widget_currencies || [], 'widget');

            } catch (error) {
                showMessage('Ошибка загрузки настроек: ' + error.message, 'error');
            }
        }

        // Функция рендера чекбоксов для валют
        function renderCurrencyCheckboxes(containerId, selectedCurrencies, type) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';

            const colorClass = type === 'cbr' ? 'blue' : 'green';

            availableCurrencies.forEach(currency => {
                const isChecked = selectedCurrencies.includes(currency.code);
                const checkbox = document.createElement('label');
                checkbox.className = 'flex items-center p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors';
                checkbox.setAttribute('data-currency', (currency.code + ' ' + currency.name).toLowerCase());
                checkbox.innerHTML = `
                    <input
                        type="checkbox"
                        name="${type === 'cbr' ? 'cbr_fetch_currencies[]' : 'widget_currencies[]'}"
                        value="${currency.code}"
                        ${isChecked ? 'checked' : ''}
                        class="w-4 h-4 text-${colorClass}-600 bg-gray-100 border-gray-300 rounded focus:ring-${colorClass}-500 focus:ring-2"
                    >
                    <div class="ml-3 flex items-center justify-between flex-1">
                        <div>
                            <div class="font-medium text-gray-900">${currency.code}</div>
                            <div class="text-sm text-gray-600 truncate max-w-32">${currency.name}</div>
                        </div>
                    </div>
                `;
                container.appendChild(checkbox);
            });
        }

        // Функция сохранения настроек
        async function saveSettings(event) {
            event.preventDefault();

            const formData = new FormData(document.getElementById('settingsForm'));
            const data = {
                cbr_fetch_currencies: formData.getAll('cbr_fetch_currencies[]'),
                widget_currencies: formData.getAll('widget_currencies[]'),
                widget_update_interval: parseInt(formData.get('widget_update_interval')),
            };

            // Валидация
            if (data.cbr_fetch_currencies.length === 0) {
                showMessage('Выберите хотя бы одну валюту для загрузки из ЦБ', 'error');
                return;
            }

            if (data.widget_currencies.length === 0) {
                showMessage('Выберите хотя бы одну валюту для отображения в виджете', 'error');
                return;
            }

            if (data.widget_update_interval < 10 || data.widget_update_interval > 3600) {
                showMessage('Интервал обновления должен быть от 10 до 3600 секунд', 'error');
                return;
            }

            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.textContent = 'Сохранение...';

            try {
                const response = await fetch('/api/v1/settings', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(data),
                });

                if (response.ok) {
                    showMessage('Настройки успешно сохранены!', 'success');
                } else {
                    const errorData = await response.json();
                    showMessage('Ошибка сохранения: ' + (errorData.message || 'Неизвестная ошибка'), 'error');
                }
            } catch (error) {
                showMessage('Ошибка сети: ' + error.message, 'error');
            } finally {
                saveButton.disabled = false;
                saveButton.textContent = 'Сохранить настройки';
            }
        }

        // Функция отображения сообщений
        function showMessage(text, type = 'info') {
            const messageDiv = document.getElementById('message');
            const messageContent = document.getElementById('messageContent');

            let bgClass = 'bg-blue-50 border-blue-200';
            let textClass = 'text-blue-800';
            let icon = '';

            switch (type) {
                case 'success':
                    bgClass = 'bg-green-50 border-green-200';
                    textClass = 'text-green-800';
                    icon = `<svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>`;
                    break;
                case 'error':
                    bgClass = 'bg-red-50 border-red-200';
                    textClass = 'text-red-800';
                    icon = `<svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>`;
                    break;
                case 'warning':
                    bgClass = 'bg-yellow-50 border-yellow-200';
                    textClass = 'text-yellow-800';
                    icon = `<svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>`;
                    break;
            }

            messageContent.className = `border rounded-lg p-4 ${bgClass}`;
            messageContent.innerHTML = `
                ${icon}
                <div class="${textClass} font-medium">${text}</div>
            `;

            messageDiv.classList.remove('hidden');

            // Автоматическое скрытие через 5 секунд
            setTimeout(() => {
                messageDiv.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>
</html>