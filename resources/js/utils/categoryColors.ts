export interface ColorTheme {
    bgLight: string;
    bgHover: string;
    text: string;
    border: string;
    badgeBg: string;
    badgeText: string;
    gradient: string;
    ringColor: string;
    glowColor: string;
}

export function getIconColorTheme(): ColorTheme {
    return {
        bgLight: 'bg-primary-light/50',
        bgHover: 'hover:bg-primary-light',
        text: 'text-primary',
        border: 'border-primary-light/50',
        badgeBg: 'bg-primary-light',
        badgeText: 'text-primary',
        gradient: 'from-primary-medium to-primary',
        ringColor: 'focus:ring-primary-light',
        glowColor: 'shadow-primary-light',
    };
}
