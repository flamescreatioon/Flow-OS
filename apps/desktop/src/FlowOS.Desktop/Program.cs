using System;
using System.Windows;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;

namespace FlowOS.Desktop;

/// <summary>
/// Main entry point for the flowOS Desktop application.
/// </summary>
public class Program
{
    [STAThread]
    public static void Main(string[] args)
    {
        var builder = Host.CreateApplicationBuilder(args);

        // Configure services
        builder.Services.AddLogging(logging =>
        {
            logging.AddConsole();
            logging.AddDebug();
        });

        // Add application services
        ConfigureServices(builder.Services);

        var host = builder.Build();

        // Initialize application
        var app = new App();
        app.Initialize(host.Services);
        app.Run();
    }

    private static void ConfigureServices(IServiceCollection services)
    {
        // Domain services
        // services.AddScoped<...>();

        // Application services
        // services.AddMediatR(cfg => cfg.RegisterServicesFromAssembly(typeof(FlowOS.Application.SomeHandler).Assembly));

        // Infrastructure services
        // services.AddDbContext<FlowOSDbContext>(options =>
        //     options.UseSqlite("Data Source=flowos.db"));

        // Sync services
        // services.AddHostedService<SyncBackgroundService>();
    }
}

/// <summary>
/// WPF Application class.
/// </summary>
public partial class App : Application
{
    private IServiceProvider? _serviceProvider;

    public void Initialize(IServiceProvider serviceProvider)
    {
        _serviceProvider = serviceProvider;
    }

    protected override void OnStartup(StartupEventArgs e)
    {
        base.OnStartup(e);

        // Create and show main window
        var mainWindow = new MainWindow();
        mainWindow.Show();
    }

    protected override void OnExit(ExitEventArgs e)
    {
        base.OnExit(e);
    }
}
