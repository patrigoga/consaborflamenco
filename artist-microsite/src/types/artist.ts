export interface Artist {
  id: string;
  slug: string;
  name: string;
  stageName?: string;
  specialty?: string;
  location?: string;
  bio?: string; // sanitized HTML
  heroImage?: string;
  coverImage?: string;
  gallery?: GalleryImage[];
  videos?: VideoItem[];
  shows?: Show[];
  events?: EventItem[];
  services?: ServiceItem[];
  press?: PressItem[];
  reviews?: Review[];
  social?: { instagram?: string; facebook?: string; youtube?: string; website?: string; whatsapp?: string };
  meta?: { description?: string; keywords?: string[]; ogImage?: string };
  rating?: number;
  createdAt?: string;
  updatedAt?: string;
}

export interface GalleryImage { id: string; url: string; alt?: string; width?: number; height?: number; }
export interface VideoItem { id: string; provider: 'youtube'|'vimeo'; providerId: string; title?: string; thumbnail?: string; }
export interface Show { id: string; title: string; description?: string; durationMins?: number; artistsCount?: number; image?: string; }
export interface EventItem { id: string; title: string; date: string; time?: string; venue?: string; city?: string; ticketUrl?: string; }
export interface PressItem { id: string; title: string; url?: string; type?: 'article'|'pdf'|'interview'; publishedAt?: string; excerpt?: string; }
export interface ServiceItem { id: string; title: string; priceFrom?: number; description?: string; durationMins?: number; }
export interface Review { id: string; author?: string; rating: number; comment?: string; date?: string; }
