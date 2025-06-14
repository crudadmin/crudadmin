import _ from 'lodash';

const components = import.meta.glob('./components/**/*.vue', { eager: true });

Object.keys(components).forEach((fileName) => {
    // Get PascalCase name of component
    const componentName = _.upperFirst(
        _.camelCase(
            // Gets the file name regardless of folder depth
            fileName
                .split('/')
                .pop()
                .replace(/\.\w+$/, '')
        )
    );

    window.crudadmin.components[componentName] = components[fileName].default;
});
