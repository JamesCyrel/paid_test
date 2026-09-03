import { Head, useForm, usePage } from '@inertiajs/react';
import {
    Video,
    ShieldCheck,
    Sparkles,
    Loader2,
    AlertCircle,
    BookOpen,
    Cpu,
    ExternalLink,
    Radio,
    UserCheck,
} from 'lucide-react';
import React from 'react';

interface PageProps {
    flash?: {
        error?: string;
        success?: string;
    };
    [key: string]: unknown;
}

export default function Welcome() {
    const { flash } = usePage<PageProps>().props;

    const { data, setData, post, processing } = useForm({
        fullName: 'James (Tutor)',
    });

    const handleJoin = (e: React.FormEvent) => {
        e.preventDefault();
        post('/meetings/join');
    };

    return (
        <>
            <Head title="BigBlueButton Classroom Integration Trial" />

            <div className="relative flex min-h-screen flex-col justify-between overflow-hidden bg-neutral-950 font-sans text-neutral-100 antialiased selection:bg-blue-600 selection:text-white">
                {/* Background decorative gradients */}
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_50%_0%,rgba(37,99,235,0.15),transparent_50%)]" />
                <div className="pointer-events-none absolute top-1/3 left-1/2 h-[500px] w-[700px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-600/10 blur-[130px]" />

                {/* Top Navigation / Status bar */}
                <header className="relative z-10 border-b border-neutral-800/80 bg-neutral-900/40 px-6 py-4 backdrop-blur-md">
                    <div className="mx-auto flex max-w-5xl items-center justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-xl border border-blue-500/30 bg-blue-600/20 text-blue-400 shadow-inner">
                                <Video className="h-5 w-5" />
                            </div>
                            <div>
                                <span className="flex items-center gap-2 text-sm font-semibold tracking-tight text-white">
                                    BigBlueButton Integration
                                    <span className="rounded-full border border-blue-500/20 bg-blue-500/10 px-2 py-0.5 text-[10px] font-bold tracking-widest text-blue-400 uppercase">
                                        Trial Proof-of-Concept
                                    </span>
                                </span>
                                <p className="text-xs text-neutral-400">
                                    Laravel 13 Direct API Architecture
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 rounded-full border border-neutral-700/50 bg-neutral-800/60 px-3 py-1.5 text-xs text-neutral-300">
                            <span className="relative flex h-2 w-2">
                                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            <span className="font-mono text-[11px] text-neutral-300">
                                BBB Test Server Live
                            </span>
                        </div>
                    </div>
                </header>

                {/* Main Content Area */}
                <main className="relative z-10 mx-auto flex w-full max-w-5xl flex-1 flex-col items-center justify-center px-6 py-12">
                    {/* Error Banner if API fails */}
                    {flash?.error && (
                        <div className="animate-in fade-in slide-in-from-top-2 mb-6 flex w-full max-w-xl items-start gap-3 rounded-xl border border-red-500/30 bg-red-950/40 p-4 text-red-200 shadow-lg shadow-red-950/20 backdrop-blur-md duration-300">
                            <AlertCircle className="mt-0.5 h-5 w-5 shrink-0 text-red-400" />
                            <div className="text-xs leading-relaxed">
                                <p className="mb-1 font-semibold text-red-300">
                                    Classroom Creation Error
                                </p>
                                <p>{flash.error}</p>
                            </div>
                        </div>
                    )}

                    {/* Primary Action Card */}
                    <div className="group relative w-full max-w-xl overflow-hidden rounded-2xl border border-neutral-800/90 bg-neutral-900/60 p-8 text-center shadow-2xl shadow-black/50 backdrop-blur-xl">
                        {/* Card Glow Highlight */}
                        <div className="pointer-events-none absolute -top-24 left-1/2 h-48 w-48 -translate-x-1/2 rounded-full bg-blue-500/20 blur-3xl" />

                        <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-neutral-700/60 bg-neutral-800/80 px-3 py-1 text-xs text-neutral-300">
                            <Sparkles className="h-3.5 w-3.5 text-blue-400" />
                            <span>Part 1 & 2 Technical Trial</span>
                        </div>

                        <h1 className="mb-3 text-2xl font-bold tracking-tight text-white sm:text-3xl">
                            Launch BigBlueButton Classroom
                        </h1>

                        <p className="mx-auto mb-8 max-w-md text-sm leading-relaxed text-neutral-400">
                            Clicking the button below programmatically
                            provisions a brand new, isolated BigBlueButton
                            classroom session via direct API and redirects you
                            instantly as{' '}
                            <span className="font-medium text-neutral-200">
                                Tutor (Moderator)
                            </span>
                            .
                        </p>

                        <form onSubmit={handleJoin} className="space-y-5">
                            <div className="text-left">
                                <label
                                    htmlFor="tutorName"
                                    className="mb-1.5 block flex items-center justify-between text-xs font-medium text-neutral-400"
                                >
                                    <span>Tutor Display Name</span>
                                    <span className="text-[11px] text-neutral-500">
                                        Enters as Moderator
                                    </span>
                                </label>
                                <div className="relative">
                                    <input
                                        id="tutorName"
                                        type="text"
                                        value={data.fullName}
                                        onChange={(e) =>
                                            setData('fullName', e.target.value)
                                        }
                                        disabled={processing}
                                        className="w-full rounded-xl border border-neutral-800 bg-neutral-950/70 px-4 py-2.5 text-sm text-white placeholder-neutral-500 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40 focus:outline-none disabled:opacity-50"
                                        placeholder="Enter your name"
                                        required
                                    />
                                    <div className="absolute top-1/2 right-3 -translate-y-1/2 text-neutral-500">
                                        <UserCheck className="h-4 w-4 text-neutral-400" />
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="group/btn relative flex w-full cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-blue-600/25 transition-all duration-150 hover:from-blue-500 hover:to-indigo-500 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing ? (
                                    <>
                                        <Loader2 className="h-5 w-5 animate-spin" />
                                        <span>
                                            Creating Unique Classroom &amp;
                                            Joining...
                                        </span>
                                    </>
                                ) : (
                                    <>
                                        <Video className="h-5 w-5" />
                                        <span>Join Meeting</span>
                                    </>
                                )}
                            </button>
                        </form>

                        <div className="mt-6 flex items-center justify-center gap-4 border-t border-neutral-800/60 pt-5 text-xs text-neutral-400">
                            <span className="flex items-center gap-1.5">
                                <Radio className="h-3.5 w-3.5 text-emerald-400" />
                                Unique UUID Room per click
                            </span>
                            <span className="text-neutral-700">•</span>
                            <span className="flex items-center gap-1.5">
                                <ShieldCheck className="h-3.5 w-3.5 text-blue-400" />
                                Moderator Access
                            </span>
                        </div>
                    </div>

                    {/* Architectural Feature Cards */}
                    <div className="mt-12 grid w-full max-w-4xl grid-cols-1 gap-4 md:grid-cols-3">
                        <div className="rounded-xl border border-neutral-800/80 bg-neutral-900/30 p-5 backdrop-blur-sm transition-all hover:border-neutral-700/80">
                            <div className="mb-3 flex h-8 w-8 items-center justify-center rounded-lg border border-blue-500/20 bg-blue-500/10 text-blue-400">
                                <Cpu className="h-4 w-4" />
                            </div>
                            <h3 className="mb-1 text-sm font-semibold text-white">
                                Direct API Communication
                            </h3>
                            <p className="text-xs leading-relaxed text-neutral-400">
                                Communicates directly with BigBlueButton REST
                                endpoints without Greenlight. Every request is
                                signed with SHA-1 HMAC checksums.
                            </p>
                        </div>

                        <div className="rounded-xl border border-neutral-800/80 bg-neutral-900/30 p-5 backdrop-blur-sm transition-all hover:border-neutral-700/80">
                            <div className="mb-3 flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-500/20 bg-emerald-500/10 text-emerald-400">
                                <ShieldCheck className="h-4 w-4" />
                            </div>
                            <h3 className="mb-1 text-sm font-semibold text-white">
                                Unique UUID Rooms
                            </h3>
                            <p className="text-xs leading-relaxed text-neutral-400">
                                Each button press generates a fresh{' '}
                                <code className="font-mono text-[11px] text-emerald-400">
                                    trial-&#123;uuid&#125;
                                </code>{' '}
                                room ID. Returning and clicking again creates a
                                brand new classroom.
                            </p>
                        </div>

                        <div className="rounded-xl border border-neutral-800/80 bg-neutral-900/30 p-5 backdrop-blur-sm transition-all hover:border-neutral-700/80">
                            <div className="mb-3 flex h-8 w-8 items-center justify-center rounded-lg border border-indigo-500/20 bg-indigo-500/10 text-indigo-400">
                                <BookOpen className="h-4 w-4" />
                            </div>
                            <h3 className="mb-1 text-sm font-semibold text-white">
                                BBB Resources Plugin
                            </h3>
                            <p className="text-xs leading-relaxed text-neutral-400">
                                Dynamically injects the custom Resources plugin
                                via{' '}
                                <code className="font-mono text-[11px] text-indigo-400">
                                    pluginManifests
                                </code>
                                , exposing a visible button inside the
                                classroom.
                            </p>
                        </div>
                    </div>
                </main>

                {/* Footer */}
                <footer className="relative z-10 border-t border-neutral-800/60 bg-neutral-950/80 px-6 py-4 text-xs text-neutral-500">
                    <div className="mx-auto flex max-w-5xl flex-col items-center justify-between gap-3 text-center sm:flex-row sm:text-left">
                        <p>
                            © 2026 Technical Trial — BigBlueButton &amp; Laravel
                            Integration
                        </p>
                        <div className="flex items-center gap-4 text-neutral-400">
                            <a
                                href="https://docs.bigbluebutton.org/development/api/"
                                target="_blank"
                                rel="noreferrer"
                                className="flex items-center gap-1 transition-colors hover:text-white"
                            >
                                BBB API Docs{' '}
                                <ExternalLink className="h-3 w-3" />
                            </a>
                            <a
                                href="https://docs.bigbluebutton.org/plugins/"
                                target="_blank"
                                rel="noreferrer"
                                className="flex items-center gap-1 transition-colors hover:text-white"
                            >
                                Plugin SDK <ExternalLink className="h-3 w-3" />
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
