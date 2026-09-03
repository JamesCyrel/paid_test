const path = require('path');

module.exports = {
    entry: './src/index.tsx',
    output: {
        filename: 'ResourcesPlugin.js',
        path: path.resolve(__dirname, 'dist'),
        library: {
            type: 'umd',
            name: 'ResourcesPlugin',
        },
        globalObject: 'this',
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.js', '.jsx'],
    },
    module: {
        rules: [
            {
                test: /\.(ts|tsx|js|jsx)$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            '@babel/preset-env',
                            ['@babel/preset-react', { runtime: 'automatic' }],
                            '@babel/preset-typescript',
                        ],
                    },
                },
            },
        ],
    },
    externals: {
        react: 'React',
        'react-dom': 'ReactDOM',
    },
};
