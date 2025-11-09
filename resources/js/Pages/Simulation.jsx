import { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';

export default function Simulation({ league }) {
    const [editingMatch, setEditingMatch] = useState(null); // {week, matchIndex}
    const [editScores, setEditScores] = useState({ home: 0, away: 0 });

    const standings = league?.standings ?? [];
    const predictions = league?.predictions ?? {};
    const upcoming = league?.upcomingMatches ?? [];
    const seasonComplete = Boolean(league?.seasonComplete);
    const weeklyResults = league?.weeklyResults ?? [];

    const formattedPredictions = useMemo(() => {
        if (!predictions || Object.keys(predictions).length === 0) {
            return standings.map((row) => ({ team: row.team, value: 0 }));
        }

        return standings.map((row) => ({
            team: row.team,
            value: Number(predictions[row.teamId] ?? 0),
        }));
    }, [predictions, standings]);

    const callAction = (routeName) => {
        router.post(route(routeName), {}, {
            preserveState: false,
            preserveScroll: true,
            only: ['league']
        });
    };

    const playNextWeek = () => callAction('league.playWeek');
    const playAllWeeks = () => callAction('league.playAll');
    const resetData = () => callAction('league.reset');
    
    const startEditMatch = (week, matchIndex, homeScore, awayScore) => {
        setEditingMatch({ week, matchIndex });
        setEditScores({ home: homeScore, away: awayScore });
    };
    
    const cancelEdit = () => {
        setEditingMatch(null);
        setEditScores({ home: 0, away: 0 });
    };
    
    const saveEdit = () => {
        if (!editingMatch) return;
        
        router.post(route('league.editMatch'), {
            week: editingMatch.week,
            match_index: editingMatch.matchIndex,
            home_score: editScores.home,
            away_score: editScores.away
        }, {
            preserveState: false,
            preserveScroll: true,
            onSuccess: () => cancelEdit(),
            onError: () => alert('Failed to update match result')
        });
    };

    return (
        <div className="min-h-screen bg-slate-50 p-8">
            <Head title="Simulation" />
            <div className="max-w-6xl mx-auto space-y-8">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold text-slate-900">League Simulation</h1>
                        <p className="text-slate-500">
                            Week {league?.currentWeek ?? 1} / {league?.totalWeeks ?? 0}
                        </p>
                    </div>
                    <div className="flex gap-3">
                        <Link
                            href={route('fixtures')}
                            className="px-4 py-2 bg-slate-200 text-slate-800 rounded-lg shadow"
                        >
                            Back to Fixtures
                        </Link>
                        <Link
                            href={route('home')}
                            className="px-4 py-2 bg-slate-900 text-white rounded-lg shadow"
                        >
                            Home
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <section className="bg-white rounded-2xl shadow p-6 md:col-span-2">
                        <h2 className="text-xl font-semibold text-slate-800 mb-4">League Standings</h2>
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="text-slate-500">
                                        <th className="py-2">#</th>
                                        <th className="py-2">Team</th>
                                        <th className="py-2">P</th>
                                        <th className="py-2">W</th>
                                        <th className="py-2">D</th>
                                        <th className="py-2">L</th>
                                        <th className="py-2">GF</th>
                                        <th className="py-2">GA</th>
                                        <th className="py-2">GD</th>
                                        <th className="py-2">Pts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {standings.map((row, index) => (
                                        <tr key={row.teamId} className="border-t border-slate-100 text-slate-800">
                                            <td className="py-2 text-slate-500">{index + 1}</td>
                                            <td className="py-2 font-medium">{row.team}</td>
                                            <td className="py-2">{row.played}</td>
                                            <td className="py-2">{row.win}</td>
                                            <td className="py-2">{row.draw}</td>
                                            <td className="py-2">{row.loss}</td>
                                            <td className="py-2">{row.gf}</td>
                                            <td className="py-2">{row.ga}</td>
                                            <td className="py-2">{row.goalDifference}</td>
                                            <td className="py-2 font-semibold">{row.points}</td>
                                        </tr>
                                    ))}
                                    {standings.length === 0 && (
                                        <tr>
                                            <td colSpan="10" className="py-4 text-center text-slate-500">
                                                No data available yet.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="bg-white rounded-2xl shadow p-6">
                        <h2 className="text-xl font-semibold text-slate-800 mb-4">
                            {seasonComplete ? 'Season Complete' : `Week ${league?.currentWeek ?? 1} Matches`}
                        </h2>
                        <div className="space-y-3">
                            {seasonComplete ? (
                                <p className="text-slate-500">All weeks played. Reset to start over.</p>
                            ) : upcoming.length > 0 ? (
                                upcoming.map((match) => (
                                    <div
                                        key={match.id ?? `${match.home}-${match.away}`}
                                        className="p-4 bg-slate-50 rounded-xl flex items-center justify-between"
                                    >
                                        <span className="font-medium text-slate-700">{match.home}</span>
                                        <span className="text-slate-400 text-sm">vs</span>
                                        <span className="font-medium text-slate-700">{match.away}</span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-slate-500">No upcoming matches.</p>
                            )}
                        </div>
                    </section>

                    <section className="bg-white rounded-2xl shadow p-6">
                        <h2 className="text-xl font-semibold text-slate-800 mb-4">Championship Predictions</h2>
                        <div className="space-y-3">
                            {formattedPredictions.map(({ team, value }) => (
                                <div key={team} className="flex items-center justify-between">
                                    <span className="font-medium text-slate-700">{team}</span>
                                    <span className="text-slate-500">{value.toFixed(2)}%</span>
                                </div>
                            ))}
                            {formattedPredictions.length === 0 && (
                                <p className="text-slate-500">No prediction data.</p>
                            )}
                        </div>
                    </section>
                </div>

                <div className="bg-white rounded-2xl shadow p-6 flex flex-wrap gap-4 justify-between items-center">
                    <p className="text-slate-600">
                        {seasonComplete
                            ? 'Season completed. You can reset to run again.'
                            : 'Use buttons to control the simulation.'}
                    </p>
                    <div className="flex flex-wrap gap-3">
                        <button
                            onClick={playAllWeeks}
                            disabled={seasonComplete}
                            className="px-4 py-3 bg-slate-900 text-white rounded-lg "
                        >
                            Play All Weeks
                        </button>
                        <button
                            onClick={playNextWeek}
                            disabled={seasonComplete}
                            className="px-4 py-3 bg-cyan-500 text-white rounded-lg "
                        >
                            Play Next Week
                        </button>
                        <button
                            onClick={resetData}
                            className="px-4 py-3 bg-slate-200 text-slate-800 rounded-lg "
                        >
                            Reset Data
                        </button>
                    </div>
                </div>
                {seasonComplete && weeklyResults.length > 0 && (
                    <div className="bg-white rounded-2xl shadow p-6">
                        <h2 className="text-xl font-semibold text-slate-800 mb-6">🏆 Season Results - All Matches</h2>
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {weeklyResults.map((week) => (
                                <div key={week.week} className="border rounded-lg p-4">
                                    <h3 className="text-lg font-medium text-slate-700 mb-3">Week {week.week}</h3>
                                    <div className="space-y-2">
                                        {week.matches.map((match, index) => {
                                            const isEditing = editingMatch?.week === week.week && editingMatch?.matchIndex === index;
                                            
                                            return (
                                                <div key={index} className="flex justify-between items-center text-sm bg-slate-50 p-2 rounded">
                                                    <span className="font-medium text-slate-800">{match.home}</span>
                                                    
                                                    {isEditing ? (
                                                        <div className="flex items-center gap-1">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max="20"
                                                                value={editScores.home}
                                                                onChange={(e) => setEditScores(prev => ({ ...prev, home: parseInt(e.target.value) || 0 }))}
                                                                className="w-12 text-center text-xs text-slate-900 border rounded px-1 py-0.5"
                                                            />
                                                            <span className="text-slate-500">-</span>
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max="20"
                                                                value={editScores.away}
                                                                onChange={(e) => setEditScores(prev => ({ ...prev, away: parseInt(e.target.value) || 0 }))}
                                                                className="w-12 text-center text-xs text-slate-900 border rounded px-1 py-0.5"
                                                            />
                                                            <button
                                                                onClick={saveEdit}
                                                                className="ml-1 px-2 py-0.5 bg-green-500 text-white text-xs rounded hover:bg-green-600"
                                                            >
                                                                ✓
                                                            </button>
                                                            <button
                                                                onClick={cancelEdit}
                                                                className="px-2 py-0.5 bg-gray-500 text-white text-xs rounded hover:bg-gray-600"
                                                            >
                                                                ✗
                                                            </button>
                                                        </div>
                                                    ) : (
                                                        <span 
                                                            className="px-2 py-1 bg-slate-200 rounded font-bold text-slate-900 cursor-pointer hover:bg-slate-300 transition-colors"
                                                            onClick={() => startEditMatch(week.week, index, match.home_score, match.away_score)}
                                                            title="Click to edit score"
                                                        >
                                                            {match.home_score} - {match.away_score}
                                                        </span>
                                                    )}
                                                    
                                                    <span className="font-medium text-slate-800">{match.away}</span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
