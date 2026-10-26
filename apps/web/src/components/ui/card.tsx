import type { HTMLAttributes } from 'react';
import { cn } from '@/utils/cn';

export type CardProps = HTMLAttributes<HTMLDivElement>;

export function Card({ className, ...props }: CardProps) {
  return (
    <div className={cn('rounded-2xl bg-surface p-6', className)} {...props} />
  );
}

export function CardTitle({ className, ...props }: CardProps) {
  return (
    <h2
      className={cn('text-base font-semibold text-on-surface', className)}
      {...props}
    />
  );
}
