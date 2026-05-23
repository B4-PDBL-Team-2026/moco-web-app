import * as PhosphorIcons from '@phosphor-icons/react';
import type { Icon } from '@phosphor-icons/react';

// Static map — all seeded system categories
const STATIC_MAP: Record<string, Icon> = {
    bowl_food: PhosphorIcons.BowlFoodIcon,
    taxi: PhosphorIcons.TaxiIcon,
    invoice: PhosphorIcons.InvoiceIcon,
    student: PhosphorIcons.StudentIcon,
    film_reel: PhosphorIcons.FilmReelIcon,
    shopping_bag: PhosphorIcons.ShoppingBagIcon,
    heart_beat: PhosphorIcons.HeartbeatIcon,
    hand_heart: PhosphorIcons.HandHeartIcon,
    tag: PhosphorIcons.TagIcon,
    money: PhosphorIcons.MoneyIcon,
    wallet: PhosphorIcons.WalletIcon,
    hand_coins: PhosphorIcons.HandCoinsIcon,
    gift: PhosphorIcons.GiftIcon,

    // Common user-created category aliases
    coffee: PhosphorIcons.CoffeeIcon,
    car: PhosphorIcons.CarIcon,
    house: PhosphorIcons.HouseIcon,
    phone: PhosphorIcons.PhoneIcon,
    heart: PhosphorIcons.HeartIcon,
    star: PhosphorIcons.StarIcon,
    book: PhosphorIcons.BookIcon,
    music_note: PhosphorIcons.MusicNoteIcon,
    game_controller: PhosphorIcons.GameControllerIcon,
    airplane: PhosphorIcons.AirplaneIcon,
    train: PhosphorIcons.TrainIcon,
    bicycle: PhosphorIcons.BicycleIcon,
    basket: PhosphorIcons.BasketIcon,
    scissors: PhosphorIcons.ScissorsIcon,
    baby: PhosphorIcons.BabyIcon,
    paw_print: PhosphorIcons.PawPrintIcon,
    plant: PhosphorIcons.PlantIcon,
    chart_line: PhosphorIcons.ChartLineIcon,
    chart_bar: PhosphorIcons.ChartBarIcon,
    credit_card: PhosphorIcons.CreditCardIcon,
    bank: PhosphorIcons.BankIcon,
    calculator: PhosphorIcons.CalculatorIcon,
    receipt: PhosphorIcons.ReceiptIcon,
    percent: PhosphorIcons.PercentIcon,
    lightning: PhosphorIcons.LightningIcon,
    drop: PhosphorIcons.DropIcon,
    flame: PhosphorIcons.FlameIcon,
    plus: PhosphorIcons.PlusIcon,
    lainnya: PhosphorIcons.TagIcon,
};

// Dynamic PascalCase converter
// "bowl_food" → "BowlFoodIcon", so user-created categories resolve automatically
function toPascalCaseIcon(snake: string): string {
    return (
        snake
            .split('_')
            .map((w) => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
            .join('') + 'Icon'
    );
}

// Resolver with cache
const resolvedCache: Record<string, Icon> = {};

export function getPhosphorIcon(iconName?: string | null): Icon {
    if (!iconName) return PhosphorIcons.QuestionIcon;

    const key = iconName.toLowerCase().trim();
    if (resolvedCache[key]) return resolvedCache[key];

    // Step 1: static map
    if (STATIC_MAP[key]) {
        resolvedCache[key] = STATIC_MAP[key];
        return STATIC_MAP[key];
    }

    // Step 2: dynamic lookup — "some_icon" → "SomeIconIcon"
    const pascalKey = toPascalCaseIcon(key);
    const dynamicIcon = (PhosphorIcons as Record<string, unknown>)[pascalKey];
    if (typeof dynamicIcon === 'function') {
        resolvedCache[key] = dynamicIcon as Icon;
        return dynamicIcon as Icon;
    }

    resolvedCache[key] = PhosphorIcons.QuestionIcon;
    return PhosphorIcons.QuestionIcon;
}

// ── Component — fixes "Cannot create components during render" ────────────────
// We must NOT call getPhosphorIcon() inline inside JSX and use the result as
// a component tag. Instead we resolve it outside and render with createElement.

interface CategoryIconProps {
    iconName?: string | null;
    size?: number;
    className?: string;
    weight?: 'thin' | 'light' | 'regular' | 'bold' | 'fill' | 'duotone';
}

export function CategoryPhosphorIcon({
    iconName,
    size = 24,
    className = '',
    weight = 'regular',
}: CategoryIconProps) {
    // getPhosphorIcon returns a stable reference from the cache/static map,
    // so this is NOT creating a new component on each render.
    const ResolvedIcon = getPhosphorIcon(iconName);
    // eslint-disable-next-line react-hooks/static-components
    return <ResolvedIcon size={size} weight={weight} className={className} />;
}
