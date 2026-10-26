import { cn } from '@/utils/cn';

export interface AvatarProps {
  name: string;
  className?: string;
}

export function Avatar({ name, className }: AvatarProps) {
  const initials = name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join('');

  return (
    <span
      aria-hidden="true"
      className={cn(
        'inline-flex h-10 w-10 shrink-0 select-none items-center justify-center rounded-full bg-primary-container text-sm font-semibold text-on-primary-container',
        className,
      )}
    >
      {initials || '?'}
    </span>
  );
}
