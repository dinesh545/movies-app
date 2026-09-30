import SwiftUI

struct SeriesListView: View {
    @State private var seriesList: [MediaItem] = []
    @State private var isLoading = true
    @State private var errorMessage: String? = nil
    @State private var searchText = ""
    @State private var selectedSeries: MediaItem? = nil
    
    var filteredSeries: [MediaItem] {
        if searchText.isEmpty {
            return seriesList
        } else {
            return seriesList.filter { $0.title.localizedCaseInsensitiveContains(searchText) }
        }
    }
    
    var body: some View {
        NavigationView {
            ZStack {
                Color.black.ignoresSafeArea()
                
                if isLoading {
                    ProgressView("Loading Web Series...")
                        .foregroundColor(.white)
                } else if let error = errorMessage {
                    VStack {
                        Text(error).foregroundColor(.red)
                        Button("Retry") { loadSeries() }
                            .padding(.top)
                    }
                } else {
                    ScrollView {
                        VStack(alignment: .leading, spacing: 15) {
                            HStack {
                                Image(systemName: "magnifyingglass").foregroundColor(.gray)
                                TextField("Search series...", text: $searchText)
                                    .foregroundColor(.white)
                            }
                            .padding(12)
                            .background(Color.white.opacity(0.15))
                            .cornerRadius(10)
                            .padding(.horizontal)
                            
                            LazyVGrid(columns: [GridItem(.flexible()), GridItem(.flexible())], spacing: 15) {
                                ForEach(filteredSeries) { series in
                                    NavigationLink(destination: SeriesDetailView(seriesId: series.id, seriesTitle: series.title)) {
                                        MediaCardView(item: series)
                                    }
                                }
                            }
                            .padding(.horizontal)
                        }
                    }
                }
            }
            .navigationTitle("Web Series")
            .navigationBarTitleDisplayMode(.inline)
            .onAppear {
                if seriesList.isEmpty { loadSeries() }
            }
        }
    }
    
    private func loadSeries() {
        isLoading = true
        errorMessage = nil
        NetworkService.shared.fetchSeriesList { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let data):
                    self.seriesList = data
                case .failure(let err):
                    self.errorMessage = err.localizedDescription
                }
            }
        }
    }
}

struct SeriesDetailView: View {
    let seriesId: Int
    let seriesTitle: String
    
    @State private var detail: SeriesDetailResponse? = nil
    @State private var isLoading = true
    @State private var selectedSeasonIndex = 0
    @State private var playingEpisode: Episode? = nil
    
    var body: some View {
        ZStack {
            Color.black.ignoresSafeArea()
            
            if isLoading {
                ProgressView("Loading details...")
                    .foregroundColor(.white)
            } else if let seasons = detail?.seasons, !seasons.isEmpty {
                ScrollView {
                    VStack(alignment: .leading, spacing: 16) {
                        // Banner/Poster Header
                        if let banner = detail?.series?.bannerUrl ?? detail?.series?.posterUrl {
                            AsyncImage(url: URL(string: banner)) { image in
                                image.resizable().aspectRatio(16/9, contentMode: .fill)
                            } placeholder: {
                                Rectangle().fill(Color.gray.opacity(0.3)).aspectRatio(16/9, contentMode: .fit)
                            }
                        }
                        
                        VStack(alignment: .leading, spacing: 8) {
                            Text(detail?.series?.title ?? seriesTitle)
                                .font(.title)
                                .fontWeight(.bold)
                                .foregroundColor(.white)
                            
                            if let desc = detail?.series?.description {
                                Text(desc)
                                    .font(.body)
                                    .foregroundColor(.gray)
                            }
                            
                            Divider().background(Color.gray)
                            
                            // Season Picker dropdown
                            Picker("Season", selection: $selectedSeasonIndex) {
                                ForEach(0..<seasons.count, id: \.self) { idx in
                                    Text(seasons[idx].seasonName ?? "Season \(seasons[idx].seasonNumber)").tag(idx)
                                }
                            }
                            .pickerStyle(MenuPickerStyle())
                            .padding(8)
                            .background(Color.red)
                            .foregroundColor(.white)
                            .cornerRadius(8)
                            
                            // Episodes list
                            if selectedSeasonIndex < seasons.count, let episodes = seasons[selectedSeasonIndex].episodes {
                                ForEach(episodes) { ep in
                                    Button(action: {
                                        playingEpisode = ep
                                    }) {
                                        HStack(spacing: 12) {
                                            Image(systemName: "play.circle.fill")
                                                .font(.largeTitle)
                                                .foregroundColor(.red)
                                            
                                            VStack(alignment: .leading, spacing: 4) {
                                                Text("Ep \(ep.episodeNumber ?? 0): \(ep.title ?? "")")
                                                    .font(.headline)
                                                    .foregroundColor(.white)
                                                if let duration = ep.duration {
                                                    Text(duration).font(.caption).foregroundColor(.gray)
                                                }
                                            }
                                            Spacer()
                                        }
                                        .padding()
                                        .background(Color.white.opacity(0.1))
                                        .cornerRadius(10)
                                    }
                                }
                            }
                        }
                        .padding()
                    }
                }
            } else {
                Text("No series details found").foregroundColor(.gray)
            }
        }
        .navigationTitle(seriesTitle)
        .fullScreenCover(item: $playingEpisode) { ep in
            PlayerScreen(title: ep.title ?? "Episode", videoUrlString: ep.videoUrl ?? "")
        }
        .onAppear { loadDetail() }
    }
    
    private func loadDetail() {
        NetworkService.shared.fetchSeriesDetail(seriesId: seriesId) { result in
            DispatchQueue.main.async {
                isLoading = false
                switch result {
                case .success(let data):
                    self.detail = data
                case .failure(_):
                    break
                }
            }
        }
    }
}
