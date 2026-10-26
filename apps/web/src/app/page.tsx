import { redirect } from 'next/navigation';
import { config } from '@/config';

export default function HomePage() {
  redirect(config.auth.dashboardPath);
}
