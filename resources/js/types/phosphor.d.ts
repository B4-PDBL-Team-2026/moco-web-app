declare module '@phosphor-icons/react' {
  import type * as React from 'react';
  
  export interface IconProps extends React.SVGProps<SVGSVGElement> {
    color?: string;
    size?: string | number;
    weight?: 'thin' | 'light' | 'regular' | 'bold' | 'fill' | 'duotone';
    mirrored?: boolean;
  }

  export type Icon = React.ForwardRefExoticComponent<
    IconProps & React.RefAttributes<SVGSVGElement>
  >;

  export const TrashIcon: Icon;
  export const BowlFoodIcon: Icon;
  export const TaxiIcon: Icon;
  export const InvoiceIcon: Icon;
  export const StudentIcon: Icon;
  export const FilmReelIcon: Icon;
  export const ShoppingBagIcon: Icon;
  export const HeartbeatIcon: Icon;
  export const HandHeartIcon: Icon;
  export const TagIcon: Icon;
  export const MoneyIcon: Icon;
  export const WalletIcon: Icon;
  export const HandCoinsIcon: Icon;
  export const GiftIcon: Icon;
  export const CoffeeIcon: Icon;
  export const CarIcon: Icon;
  export const HouseIcon: Icon;
  export const PhoneIcon: Icon;
  export const HeartIcon: Icon;
  export const StarIcon: Icon;
  export const BookIcon: Icon;
  export const MusicNoteIcon: Icon;
  export const GameControllerIcon: Icon;
  export const AirplaneIcon: Icon;
  export const TrainIcon: Icon;
  export const BicycleIcon: Icon;
  export const BasketIcon: Icon;
  export const ScissorsIcon: Icon;
  export const BabyIcon: Icon;
  export const PawPrintIcon: Icon;
  export const PlantIcon: Icon;
  export const ChartLineIcon: Icon;
  export const ChartBarIcon: Icon;
  export const CreditCardIcon: Icon;
  export const BankIcon: Icon;
  export const CalculatorIcon: Icon;
  export const ReceiptIcon: Icon;
  export const PercentIcon: Icon;
  export const LightningIcon: Icon;
  export const DropIcon: Icon;
  export const FlameIcon: Icon;
  export const PlusIcon: Icon;
  export const QuestionIcon: Icon;
}
