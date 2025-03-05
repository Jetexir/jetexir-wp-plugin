const fs = require("fs");
module.exports = {
    mode: 'production',
    watch: true,
    entry: (() => {
        const toReturn = {};

        const addFiles = (dirpath) => fs.readdirSync(dirpath).forEach((f) => {
            let name = f.split('.').slice(0, -1).join('.');
            if (name !== '')
                toReturn[name] = dirpath + "/" + f;
        });

        addFiles("./assets/scss");
        //  toReturn["main"] = "./js/index.js";

        console.log(toReturn);

        return toReturn;
    })(),
    module: {
        rules: [
            {
                test: /\.scss$/,
                exclude: /node_modules/,
                // loader: "sass-loader",
                use: [
                    {
                        loader: "sass-loader",
                        options: {
                            sourceMap: true,
                            sassOptions: {
                                outputStyle: "compressed",
                            },
                        },
                    },
                ],
            },
        ],
    },
};