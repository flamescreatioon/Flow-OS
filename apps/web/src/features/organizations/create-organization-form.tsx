'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { createOrganization } from '@/features/organizations/service';
import { config } from '@/config';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

function slugify(value: string): string {
  return value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export function CreateOrganizationForm() {
  const router = useRouter();
  const [name, setName] = useState('');
  const [slugEdited, setSlugEdited] = useState(false);
  const [slug, setSlug] = useState('');
  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    setPending(true);
    setError(null);
    try {
      await createOrganization({
        name: String(data.get('name') ?? ''),
        slug: String(data.get('slug') ?? ''),
        timezone: config.date.timezone,
        currency: config.currency.default,
      });
      router.refresh();
      setName('');
      setSlug('');
      setSlugEdited(false);
    } catch (caught) {
      setError(
        caught instanceof Error ? caught.message : 'Unable to create organization.',
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <form onSubmit={onSubmit} className="space-y-4" aria-label="Create organization">
      {error ? <p className="text-sm text-red-600">{error}</p> : null}
      <div className="space-y-1">
        <Label htmlFor="org-name">Organization name</Label>
        <Input
          id="org-name"
          name="name"
          required
          maxLength={255}
          value={name}
          onChange={(event) => {
            setName(event.target.value);
            if (!slugEdited) setSlug(slugify(event.target.value));
          }}
        />
      </div>
      <div className="space-y-1">
        <Label htmlFor="org-slug">Slug</Label>
        <Input
          id="org-slug"
          name="slug"
          required
          minLength={2}
          maxLength={50}
          pattern="[a-z0-9-]+"
          value={slug}
          onChange={(event) => {
            setSlugEdited(true);
            setSlug(event.target.value);
          }}
        />
        <p className="text-xs text-gray-500">
          Lowercase letters, numbers, and dashes. Used in subdomain URLs.
        </p>
      </div>
      <Button type="submit" disabled={pending || !name || !slug}>
        {pending ? 'Creating…' : 'Create organization'}
      </Button>
    </form>
  );
}
