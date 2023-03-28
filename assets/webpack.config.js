const autoprefixer = require("autoprefixer");
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    watch: true,
    entry: {
        "fs-new-mail": "../assets/src/js/fs-new-mail.js",
    },
    output: {
        filename: "[name].js",
        path: path.resolve(__dirname, "js"),
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                },
            },
            {
                test: /\.css$/,
                use: ['style-loader', 'css-loader']
            }
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "[name].css",
            chunkFilename: "[name].css",
        })
    ],
    resolve: {
        extensions: ["*", ".js", ".json"],
    },
    devServer: {
        historyApiFallback: true,
    },
};