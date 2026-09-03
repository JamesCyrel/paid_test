import * as React from 'react';
import * as ReactDOM from 'react-dom/client';
import {
    BbbPluginSdk,
    OptionsDropdownOption,
    OptionsDropdownSeparator,
    ActionsBarButton,
    ActionsBarPosition,
    PluginApi,
    pluginLogger,
} from 'bigbluebutton-html-plugin-sdk';

const TARGET_URL = 'https://example.com';

interface ResourcesPluginProps {
    pluginUuid: string;
}

export function ResourcesPlugin({
    pluginUuid,
}: ResourcesPluginProps): React.ReactElement | null {
    React.useEffect(() => {
        try {
            BbbPluginSdk.initialize(pluginUuid);
            const pluginApi: PluginApi = BbbPluginSdk.getPluginApi(pluginUuid);

            if (pluginApi) {
                // 1. Add to Options Dropdown (3-dot menu at top right)
                if (typeof pluginApi.setOptionsDropdownItems === 'function') {
                    pluginApi.setOptionsDropdownItems([
                        new OptionsDropdownSeparator(),
                        new OptionsDropdownOption({
                            label: 'Resources',
                            icon: 'file',
                            onClick: () => {
                                pluginLogger?.info?.(
                                    'Resources dropdown option clicked',
                                );
                                window.open(
                                    TARGET_URL,
                                    '_blank',
                                    'noopener,noreferrer',
                                );
                            },
                        }),
                    ]);
                }

                // 2. Add to Action Bar (bottom toolbar for immediate visibility)
                if (typeof pluginApi.setActionsBarItems === 'function') {
                    const resourcesButton = new ActionsBarButton({
                        icon: {
                            svgContent: (
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    style={{
                                        display: 'inline-block',
                                        verticalAlign: 'middle',
                                    }}
                                >
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z" />
                                    <path d="M6 6h10" />
                                    <path d="M6 10h10" />
                                </svg>
                            ) as unknown as React.SVGProps<SVGSVGElement>,
                        },
                        tooltip: 'Resources',
                        position: ActionsBarPosition.RIGHT,
                        dataTest: 'resourcesPluginButton',
                        onClick: () => {
                            pluginLogger?.info?.(
                                'Resources action bar button clicked',
                            );
                            window.open(
                                TARGET_URL,
                                '_blank',
                                'noopener,noreferrer',
                            );
                        },
                    });

                    pluginApi.setActionsBarItems([resourcesButton]);
                }
            }
        } catch (err) {
            console.warn('[BBB-Resources-Plugin] Plugin SDK init note:', err);
        }
    }, [pluginUuid]);

    return null;
}

// Auto-mount when script is loaded by BigBlueButton HTML5 client
const currentScript = document.currentScript;
const uuid = currentScript?.getAttribute('uuid') || 'bbb-resources-plugin-root';

let container = document.getElementById(uuid);
if (!container) {
    container = document.createElement('div');
    container.id = uuid;
    container.style.display = 'none';
    document.body.appendChild(container);
}

const root = ReactDOM.createRoot(container);
root.render(<ResourcesPlugin pluginUuid={uuid} />);

export default ResourcesPlugin;
