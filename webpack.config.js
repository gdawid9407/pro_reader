const path = require('path');

module.exports = {
    entry: './assets/js/script.js',  // Główny plik JS Twojego projektu
    output: {
        filename: 'bundle.js',  // Nazwa wynikowego pliku JS
        path: path.resolve(__dirname, 'assets/js'),  // Ścieżka do folderu wynikowego
    },
    module: {
        rules: [
            {
                test: /\.js$/,  // Reguła dla plików JS
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',  // Używamy Babel do przetwarzania kodu
                    options: {
                        presets: ['@babel/preset-env'],  // Przekształcanie nowoczesnego JS do starszych wersji
                    }
                }
            },
            {
                test: /\.css$/,  // Reguła dla plików CSS
                use: ['style-loader', 'css-loader']  // Ładowanie CSS
            }
        ]
    },
    resolve: {
        alias: {
            jquery: "jquery/src/jquery"  // Alias dla jQuery, ponieważ w niektórych przypadkach Webpack może mieć problem z importowaniem jQuery
        }
    },
    mode: 'production',  // Tryb produkcyjny
};
